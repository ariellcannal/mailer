<?php
namespace App\Controllers;

use App\Models\ContactListMemberModel;
use App\Models\ContactListModel;
use App\Models\ContactModel;
use CodeIgniter\HTTP\Files\UploadedFile;
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

        return view('contacts/entry', [
            'activeMenu' => 'contacts',
            'pageTitle' => 'Novo Contato',
            'lists' => $listModel->orderBy('name', 'ASC')->findAll(),
            'selectedLists' => $selectedLists,
        ]);
    }
    
    public function store() {
        $model = new ContactModel();

        $email = (string) $this->request->getPost('email');
        $name = (string) $this->request->getPost('name');
        $nickname = trim((string) $this->request->getPost('nickname'));

        if ($nickname === '') {
            $nickname = $model->generateNickname($name, $email);
        }

        $data = [
            'email' => $email,
            'name' => $name,
            'nickname' => $nickname,
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

            if (!empty($data['nickname']) && $data['nickname'] !== ($existing['nickname'] ?? '')) {
                $updates['nickname'] = $data['nickname'];
            } elseif (!isset($existing['nickname']) || trim((string) $existing['nickname']) === '') {
                $updates['nickname'] = $model->generateNickname($data['name'], $data['email']);
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
    
    public function imports() {
        $importModel = new \App\Models\ContactImportModel();
        $imports = $importModel->getImports(20);
        
        return view('contacts/imports', [
            'activeMenu' => 'contacts',
            'pageTitle' => 'Importações de Contatos',
            'imports' => $imports,
            'pager' => $importModel->pager,
        ]);
    }
    
    public function importProcess() {
        $file = $this->request->getFile('file');
        $listIds = (array) $this->request->getPost('lists');
        $emailColumn = $this->request->getPost('email_column');
        $nameColumn = $this->request->getPost('name_column');
        $tempFile = $this->request->getPost('temp_file');

        try {
            $tempPath = $this->persistImportFile($file, $tempFile);
            $rows = $this->loadSpreadsheetRows($tempPath);

            if (empty($rows)) {
                return redirect()->back()->with('contacts_error', 'Arquivo vazio ou inválido.');
            }

            $headers = array_map('trim', $rows[0]);

            // Se ainda não mapeou colunas, mostrar tela de mapeamento
            if ($emailColumn === null && count($headers) > 1) {
                return view('contacts/import_mapping', [
                    'activeMenu' => 'contacts',
                    'pageTitle' => 'Mapear Colunas',
                    'headers' => $headers,
                    'tempFile' => $tempPath,
                    'lists' => (new ContactListModel())->orderBy('name', 'ASC')->findAll(),
                    'selectedLists' => $listIds,
                ]);
            }

            $emailIndex = $emailColumn !== null ? (int) $emailColumn : 0;

            if (!isset($headers[$emailIndex])) {
                return redirect()->back()->with('contacts_error', 'Selecione a coluna de e-mail.');
            }

            // Adicionar à fila de importação
            $importModel = new \App\Models\ContactImportModel();
            $importId = $importModel->insert([
                'filename' => $file ? $file->getClientName() : basename($tempPath),
                'filepath' => $tempPath,
                'status' => 'pending',
                'total_rows' => count($rows) - 1, // Excluir header
                'email_column' => $emailColumn,
                'name_column' => $nameColumn,
                'list_ids' => !empty($listIds) ? json_encode($listIds) : null,
            ]);

            return redirect()->to('/contacts/imports')->with(
                'contacts_success',
                'Importação adicionada à fila. Acompanhe o progresso na tela de importações.'
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('contacts_error', 'Erro ao importar: ' . $e->getMessage());
        }
    }

    /**
     * Persiste o arquivo temporariamente para processamento e reutilização.
     *
     * @param \CodeIgniter\HTTP\Files\UploadedFile|null $file     Arquivo enviado.
     * @param string|null                                  $tempFile Caminho temporário recebido do passo anterior.
     * @return string Caminho do arquivo temporário.
     */
    protected function persistImportFile(?UploadedFile $file, ?string $tempFile): string
    {
        $destination = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'imports';
        if (!is_dir($destination)) {
            mkdir($destination, 0775, true);
        }

        if (!empty($tempFile) && is_file($tempFile)) {
            return $tempFile;
        }

        if ($file === null || !$file->isValid()) {
            throw new \RuntimeException('Arquivo inválido.');
        }

        $tempPath = $destination . DIRECTORY_SEPARATOR . uniqid('contacts_', true) . '.' . $file->getClientExtension();
        $file->move($destination, basename($tempPath));

        return $tempPath;
    }

    /**
     * Carrega linhas de uma planilha suportando CSV, XLS e XLSX.
     *
     * @param string $path Caminho do arquivo temporário.
     * @return array<array-key, array>
     */
    protected function loadSpreadsheetRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray();
    }

    /**
     * Formata o nome para que a primeira letra seja maiúscula.
     *
     * @param string|null $name Nome original informado.
     * @return string|null Nome formatado ou nulo.
     */
    protected function formatNameUcFirst(?string $name): ?string
    {
        $trimmed = trim((string) $name);

        if ($trimmed === '') {
            return null;
        }

        $firstChar = mb_strtoupper(mb_substr($trimmed, 0, 1, 'UTF-8'), 'UTF-8');
        $rest = mb_substr($trimmed, 1, null, 'UTF-8');

        return $firstChar . $rest;
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

        $sends = $model->getContactSends((int) $id);

        return view('contacts/view', [
            'contact' => $contact,
            'lists' => $lists,
            'sends' => $sends,
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

        return view('contacts/entry', [
            'contact' => $contact,
            'lists' => $listModel->orderBy('name', 'ASC')->findAll(),
            'selectedLists' => $selectedLists,
            'activeMenu' => 'contacts',
            'pageTitle' => 'Editar Contato'
        ]);
    }
    
    public function update($id) {
        $model = new ContactModel();

        $email = (string) $this->request->getPost('email');
        $name = (string) $this->request->getPost('name');
        $nickname = trim((string) $this->request->getPost('nickname'));

        if ($nickname === '') {
            $nickname = $model->generateNickname($name, $email);
        }

        $data = [
            'id' => (int) $id,
            'email' => $email,
            'name' => $name,
            'nickname' => $nickname,
        ];
        $listIds = (array) $this->request->getPost('lists');

        if (!$model->save($data)) {
            return redirect()->back()->with('contacts_error', 'Erro ao atualizar: ' . implode(' ', $model->errors()))->withInput();
        }

        $model->replaceContactLists((int) $id, $listIds);

        return redirect()->to('/contacts/view/' . $id)->with('contacts_success', 'Contato atualizado!');
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
