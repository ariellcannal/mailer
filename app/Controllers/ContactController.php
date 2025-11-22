<?php
namespace App\Controllers;

use App\Models\ContactListMemberModel;
use App\Models\ContactListModel;
use App\Models\ContactModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ContactController extends BaseController {
    public function index() {
        $model = new ContactModel();
        
        $filters = [
            'email' => $this->request->getGet('email'),
            'name' => $this->request->getGet('name'),
            'quality_score' => $this->request->getGet('quality_score'),
        ];
        
        $contacts = $model->getContacts($filters, 20);

        $contactIds = array_column($contacts, 'id');
        $memberModel = new ContactListMemberModel();
        $contactLists = $memberModel->getListsByContacts($contactIds);

        $listModel = new ContactListModel();
        $availableLists = $listModel->orderBy('name', 'ASC')->findAll();

        return view('contacts/index', [
            'contacts' => $contacts,
            'pager' => $model->pager,
            'filters' => $filters,
            'contactLists' => $contactLists,
            'lists' => $availableLists,
            'totalContacts' => $model->pager?->getTotal() ?? 0,
            'activeMenu' => 'contacts',
            'pageTitle' => 'Contatos'
        ]);
    }
    
    public function create() {
        $listModel = new ContactListModel();

        $selectedLists = [];
        $listParam = $this->request->getGet('list_id');
        $listArray = $this->request->getGet('lists');

        if ($listParam !== null) {
            $selectedLists[] = (int) $listParam;
        }

        if (!empty($listArray)) {
            foreach ((array) $listArray as $id) {
                $selectedLists[] = (int) $id;
            }
        }

        $selectedLists = array_values(array_unique(array_filter($selectedLists)));

        return view('contacts/create', [
            'activeMenu' => 'contacts',
            'pageTitle' => 'Novo Contato',
            'lists' => $listModel->orderBy('name', 'ASC')->findAll(),
            'selectedLists' => $selectedLists,
        ]);
    }
    
    public function store() {
        $model = new ContactModel();

        $data = [
            'email' => $this->request->getPost('email'),
            'name' => $this->request->getPost('name'),
            'is_active' => 1,
            'quality_score' => 3,
        ];
        $listIds = (array) $this->request->getPost('lists');

        $existing = $model->where('email', $data['email'])->first();

        if ($existing) {
            $updates = [];
            if (!empty($data['name']) && $data['name'] !== ($existing['name'] ?? '')) {
                $updates['name'] = $data['name'];
            }

            if (!empty($updates)) {
                $model->skipValidation(true)->update((int) $existing['id'], $updates);
            }

            if (!empty($listIds)) {
                $model->syncContactLists((int) $existing['id'], $listIds);
            }

            return redirect()->to('/contacts')->with('contacts_success', 'Contato atualizado e vinculado às listas.');
        }

        if ($contactId = $model->insert($data)) {
            $model->syncContactLists((int) $contactId, $listIds);

            return redirect()->to('/contacts')->with('contacts_success', 'Contato criado!');
        }

        return redirect()->back()->with('contacts_error', 'Erro ao criar contato')->withInput();
    }
    
    public function import() {
        $listModel = new ContactListModel();

        return view('contacts/import', [
            'activeMenu' => 'contacts',
            'pageTitle' => 'Importar Contatos',
            'lists' => $listModel->orderBy('name', 'ASC')->findAll(),
        ]);
    }
    
    public function importProcess() {
        $file = $this->request->getFile('file');
        
        if (!$file->isValid()) {
            return redirect()->back()->with('error', 'Arquivo inválido');
        }
        
        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            $contacts = [];
            $listIds = (array) $this->request->getPost('lists');
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header
                
                if (!empty($row[0])) {
                    $contacts[] = [
                        'email' => $row[0],
                        'name' => $row[1] ?? null,
                    ];
                }
            }
            
            $model = new ContactModel();
            $result = $model->importContacts($contacts, $listIds);

            return redirect()->to('/contacts')->with('contacts_success',
                "Importados: {$result['imported']}, Ignorados: {$result['skipped']}");
        } catch (\Exception $e) {
            return redirect()->back()->with('contacts_error', 'Erro ao importar: ' . $e->getMessage());
        }
    }
    
    public function view($id) {
        $model = new ContactModel();
        $contact = $model->find($id);

        if (!$contact) {
            return redirect()->to('/contacts')->with('contacts_error', 'Contato não encontrado');
        }

        $memberModel = new ContactListMemberModel();
        $lists = $memberModel
            ->where('contact_id', $id)
            ->join('contact_lists', 'contact_lists.id = contact_list_members.list_id')
            ->select('contact_lists.*')
            ->findAll();

        return view('contacts/view', [
            'contact' => $contact,
            'lists' => $lists,
            'activeMenu' => 'contacts',
            'pageTitle' => $contact['name'] ?: $contact['email']
        ]);
    }
    
    public function edit($id) {
        $model = new ContactModel();
        $contact = $model->find($id);

        if (!$contact) {
            return redirect()->to('/contacts')->with('contacts_error', 'Contato não encontrado');
        }

        $listModel = new ContactListModel();
        $memberModel = new ContactListMemberModel();

        $selectedLists = $memberModel
            ->where('contact_id', $id)
            ->findColumn('list_id') ?? [];

        return view('contacts/edit', [
            'contact' => $contact,
            'lists' => $listModel->orderBy('name', 'ASC')->findAll(),
            'selectedLists' => $selectedLists,
            'activeMenu' => 'contacts',
            'pageTitle' => 'Editar Contato'
        ]);
    }
    
    public function update($id) {
        $model = new ContactModel();

        $data = [
            'email' => $this->request->getPost('email'),
            'name' => $this->request->getPost('name'),
        ];
        $listIds = (array) $this->request->getPost('lists');

        if ($model->update($id, $data)) {
            $model->replaceContactLists($id, $listIds);

            return redirect()->to('/contacts/view/' . $id)->with('contacts_success', 'Contato atualizado!');
        }

        return redirect()->back()->with('contacts_error', 'Erro ao atualizar')->withInput();
    }

    /**
     * Vincula múltiplos contatos a uma ou mais listas.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function bulkAssignLists()
    {
        $model = new ContactModel();

        $contactIds = (array) $this->request->getPost('contacts');
        $listIds = (array) $this->request->getPost('lists');
        $selectAll = (bool) $this->request->getPost('select_all');
        $filters = (array) $this->request->getPost('filters');

        if ($selectAll) {
            $contactIds = $model->getAllContactIds($filters);
        }

        if (empty(array_filter($contactIds)) || empty(array_filter($listIds))) {
            return redirect()->back()->with('contacts_error', 'Selecione ao menos um contato e uma lista.');
        }

        $model->assignContactsToLists($contactIds, $listIds);

        return redirect()->to('/contacts')->with('contacts_success', 'Contatos adicionados às listas selecionadas.');
    }
    
    public function delete($id) {
        $model = new ContactModel();
        $memberModel = new ContactListMemberModel();
        $listModel = new ContactListModel();

        $listIds = $memberModel
            ->where('contact_id', $id)
            ->findColumn('list_id') ?? [];

        if ($model->delete($id)) {
            $listModel->refreshCounters($listIds);

            return redirect()->to('/contacts')->with('contacts_success', 'Contato excluído!');
        }

        return redirect()->back()->with('contacts_error', 'Erro ao excluir');
    }
}
