<?php
namespace App\Controllers;

use App\Models\SenderModel;
use App\Libraries\AWS\SESService;
use App\Libraries\DNS\DNSValidator;

class SenderController extends BaseController {
    public function index() {
        $model = new SenderModel();
        $senders = $model->orderBy('created_at', 'DESC')->findAll();
        
        return view('senders/index', [
            'senders' => $senders,
            'activeMenu' => 'senders',
            'pageTitle' => 'Remetentes'
        ]);
    }
    
    public function create() {
        return view('senders/create', [
            'activeMenu' => 'senders',
            'pageTitle' => 'Novo Remetente'
        ]);
    }
    
    public function store() {
        $model = new SenderModel();
        
        $email = $this->request->getPost('email');
        $name = $this->request->getPost('name');
        
        // Extrair domínio
        $domain = substr(strrchr($email, '@'), 1);
        
        $data = [
            'email' => $email,
            'name' => $name,
            'domain' => $domain,
            'is_active' => 0, // Inativo até verificar
        ];
        
        if ($senderId = $model->insert($data)) {
            // Verificar no SES
            $this->verifySender($senderId);
            
            return redirect()->to('/senders/view/' . $senderId)->with('success', 'Remetente criado! Verifique o DNS.');
        }
        
        return redirect()->back()->with('error', 'Erro ao criar remetente')->withInput();
    }
    
    public function view($id) {
        $model = new SenderModel();
        $sender = $model->find($id);

        if (!$sender) {
            return redirect()->to('/senders')->with('error', 'Remetente não encontrado');
        }

        // Validar DNS
        $validator = new DNSValidator();
        $dnsStatus = $validator->validateAll($sender['domain']);

        return view('senders/view', [
            'sender' => $sender,
            'dnsStatus' => $dnsStatus,
            'activeMenu' => 'senders',
            'pageTitle' => $sender['email']
        ]);
    }

    /**
     * Exibe formulário de edição de remetente.
     */
    public function edit(int $id)
    {
        $model = new SenderModel();
        $sender = $model->find($id);

        if (!$sender) {
            return redirect()->to('/senders')->with('error', 'Remetente não encontrado');
        }

        return view('senders/edit', [
            'sender' => $sender,
            'activeMenu' => 'senders',
            'pageTitle' => 'Editar Remetente'
        ]);
    }

    /**
     * Atualiza dados do remetente.
     */
    public function update(int $id)
    {
        $model = new SenderModel();
        $sender = $model->find($id);

        if (!$sender) {
            return redirect()->to('/senders')->with('error', 'Remetente não encontrado');
        }

        $email = $this->request->getPost('email');
        $name = $this->request->getPost('name');
        $domain = substr(strrchr($email, '@'), 1) ?: $sender['domain'];

        $model->update($id, [
            'email' => $email,
            'name' => $name,
            'domain' => $domain,
        ]);

        return redirect()->to('/senders/view/' . $id)->with('success', 'Remetente atualizado com sucesso!');
    }

    /**
     * Remove um remetente.
     */
    public function delete(int $id)
    {
        $model = new SenderModel();
        $sender = $model->find($id);

        if (!$sender) {
            return redirect()->to('/senders')->with('error', 'Remetente não encontrado');
        }

        $model->delete($id);

        return redirect()->to('/senders')->with('success', 'Remetente removido com sucesso!');
    }

    public function verify($id) {
        $this->verifySender($id);
        return redirect()->to('/senders/view/' . $id)->with('success', 'Verificação iniciada!');
    }
    
    public function checkDNS($id) {
        $model = new SenderModel();
        $sender = $model->find($id);
        
        if (!$sender) {
            return $this->response->setJSON(['success' => false, 'error' => 'Remetente não encontrado']);
        }
        
        $validator = new DNSValidator();
        $result = $validator->validateAll($sender['domain']);
        
        // Atualizar status
        $model->update($id, [
            'spf_verified' => $result['spf']['valid'] ? 1 : 0,
            'dkim_verified' => $result['dkim']['valid'] ? 1 : 0,
            'dmarc_verified' => $result['dmarc']['valid'] ? 1 : 0,
        ]);
        
        return $this->response->setJSON([
            'success' => true,
            'result' => $result
        ]);
    }
    
    protected function verifySender($id) {
        $model = new SenderModel();
        $sender = $model->find($id);
        
        if (!$sender) {
            return;
        }
        
        try {
            $ses = new SESService();
            
            // Verificar domínio
            $domainResult = $ses->verifyDomain($sender['domain']);
            
            if ($domainResult['success']) {
                $model->update($id, [
                    'ses_verification_token' => $domainResult['verificationToken'],
                ]);
            }
            
            // Habilitar DKIM
            $dkimResult = $ses->enableDKIM($sender['domain']);
            
            // Verificar status
            $statusResult = $ses->getIdentityVerificationStatus($sender['domain']);
            
            if ($statusResult['verified']) {
                $model->update($id, [
                    'ses_verified' => 1,
                    'is_active' => 1,
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error verifying sender: ' . $e->getMessage());
        }
    }
}
