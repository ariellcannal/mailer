<?php
namespace App\Controllers;

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
        
        return view('contacts/index', [
            'contacts' => $contacts,
            'pager' => $model->pager,
            'filters' => $filters,
            'activeMenu' => 'contacts',
            'pageTitle' => 'Contatos'
        ]);
    }
    
    public function create() {
        return view('contacts/create', [
            'activeMenu' => 'contacts',
            'pageTitle' => 'Novo Contato'
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
        
        if ($model->insert($data)) {
            return redirect()->to('/contacts')->with('success', 'Contato criado!');
        }
        
        return redirect()->back()->with('error', 'Erro ao criar contato')->withInput();
    }
    
    public function import() {
        return view('contacts/import', [
            'activeMenu' => 'contacts',
            'pageTitle' => 'Importar Contatos'
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
            $result = $model->importContacts($contacts);
            
            return redirect()->to('/contacts')->with('success', 
                "Importados: {$result['imported']}, Ignorados: {$result['skipped']}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao importar: ' . $e->getMessage());
        }
    }
    
    public function view($id) {
        $model = new ContactModel();
        $contact = $model->find($id);
        
        if (!$contact) {
            return redirect()->to('/contacts')->with('error', 'Contato não encontrado');
        }
        
        return view('contacts/view', [
            'contact' => $contact,
            'activeMenu' => 'contacts',
            'pageTitle' => $contact['name'] ?: $contact['email']
        ]);
    }
    
    public function edit($id) {
        $model = new ContactModel();
        $contact = $model->find($id);
        
        if (!$contact) {
            return redirect()->to('/contacts')->with('error', 'Contato não encontrado');
        }
        
        return view('contacts/edit', [
            'contact' => $contact,
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
        
        if ($model->update($id, $data)) {
            return redirect()->to('/contacts/view/' . $id)->with('success', 'Contato atualizado!');
        }
        
        return redirect()->back()->with('error', 'Erro ao atualizar')->withInput();
    }
    
    public function delete($id) {
        $model = new ContactModel();
        
        if ($model->delete($id)) {
            return redirect()->to('/contacts')->with('success', 'Contato excluído!');
        }
        
        return redirect()->back()->with('error', 'Erro ao excluir');
    }
}
