<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MessageModel;
use App\Models\CampaignModel;
use App\Models\SenderModel;
use App\Models\MessageSendModel;
use App\Models\ContactModel;
use App\Libraries\Email\QueueManager;
use CodeIgniter\HTTP\RedirectResponse;

class MessageController extends BaseController {
    public function index(): string {
        $model = new MessageModel();
        $messages = $model->orderBy('created_at', 'DESC')->paginate(20);

        $campaignModel = new CampaignModel();
        $senderModel = new SenderModel();

        $campaignMap = [];
        foreach ($campaignModel->findAll() as $campaign) {
            $campaignMap[$campaign['id']] = $campaign['name'];
        }

        $senderMap = [];
        foreach ($senderModel->findAll() as $sender) {
            $senderMap[$sender['id']] = $sender['name'];
        }

        return view('messages/index', [
            'messages' => $messages,
            'pager' => $model->pager,
            'campaignMap' => $campaignMap,
            'senderMap' => $senderMap,
            'activeMenu' => 'messages',
            'pageTitle' => 'Mensagens'
        ]);
    }
    
    public function create(): string {
        $campaignModel = new CampaignModel();
        $senderModel = new SenderModel();

        return view('messages/create', [
            'campaigns' => $campaignModel->where('is_active', 1)->findAll(),
            'senders' => $senderModel->where('is_active', 1)->where('ses_verified', 1)->findAll(),
            'activeMenu' => 'messages',
            'pageTitle' => 'Nova Mensagem',
            'editorEngine' => get_system_setting('editor_engine', 'tinymce'),
        ]);
    }

    /**
     * Exibe uma mensagem cadastrada.
     */
    public function view(int $id)
    {
        $model = new MessageModel();
        $message = $model->find($id);

        if (!$message) {
            return redirect()->to('/messages')->with('error', 'Mensagem não encontrada');
        }

        $sendModel = new MessageSendModel();
        $sends = $sendModel->where('message_id', $id)
            ->orderBy('id', 'DESC')
            ->findAll(20);

        $contactMap = [];
        if (!empty($sends)) {
            $contactIds = array_unique(array_column($sends, 'contact_id'));
            if (!empty($contactIds)) {
                $contactModel = new ContactModel();
                foreach ($contactModel->whereIn('id', $contactIds)->findAll() as $contact) {
                    $contactMap[$contact['id']] = $contact['email'];
                }
            }
        }

        return view('messages/view', [
            'message' => $message,
            'sends' => $sends,
            'contactMap' => $contactMap,
            'activeMenu' => 'messages',
            'pageTitle' => $message['subject'],
        ]);
    }

    /**
     * Exibe formulário de edição da mensagem.
     */
    public function edit(int $id): string|RedirectResponse
    {
        $model = new MessageModel();
        $message = $model->find($id);

        if (!$message) {
            return redirect()->to('/messages')->with('error', 'Mensagem não encontrada');
        }

        $campaignModel = new CampaignModel();
        $senderModel = new SenderModel();

        return view('messages/edit', [
            'message' => $message,
            'campaigns' => $campaignModel->where('is_active', 1)->findAll(),
            'senders' => $senderModel->where('is_active', 1)->where('ses_verified', 1)->findAll(),
            'activeMenu' => 'messages',
            'pageTitle' => 'Editar Mensagem',
            'editorEngine' => get_system_setting('editor_engine', 'tinymce'),
        ]);
    }

    public function store() {
        $model = new MessageModel();
        
        // Validar opt-out link
        $htmlContent = $this->request->getPost('html_content');
        $validation = $this->validateOptOutLink($htmlContent);
        
        if (!$validation['valid']) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $validation['message']
            ]);
        }
        
        $data = [
            'campaign_id' => $this->request->getPost('campaign_id'),
            'sender_id' => $this->request->getPost('sender_id'),
            'subject' => $this->request->getPost('subject'),
            'from_name' => $this->request->getPost('from_name'),
            'reply_to' => $this->request->getPost('reply_to'),
            'html_content' => $htmlContent,
            'has_optout_link' => $validation['has_optout'],
            'optout_link_visible' => $validation['is_visible'],
            'status' => 'draft',
        ];
        
        if ($messageId = $model->insert($data)) {
            // Salvar configurações de reenvio
            $this->saveResendConfig($messageId);
            
            return $this->response->setJSON([
                'success' => true,
                'message_id' => $messageId
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'error' => 'Erro ao salvar mensagem'
        ]);
    }

    /**
     * Atualiza uma mensagem existente.
     */
    public function update(int $id): RedirectResponse
    {
        $model = new MessageModel();
        $message = $model->find($id);

        if (!$message) {
            return redirect()->to('/messages')->with('error', 'Mensagem não encontrada');
        }

        $htmlContent = $this->request->getPost('html_content');
        $validation = $this->validateOptOutLink($htmlContent);

        if (!$validation['valid']) {
            return redirect()->back()->withInput()->with('error', $validation['message']);
        }

        $data = [
            'campaign_id' => $this->request->getPost('campaign_id'),
            'sender_id' => $this->request->getPost('sender_id'),
            'subject' => $this->request->getPost('subject'),
            'from_name' => $this->request->getPost('from_name'),
            'reply_to' => $this->request->getPost('reply_to'),
            'html_content' => $htmlContent,
            'has_optout_link' => $validation['has_optout'],
            'optout_link_visible' => $validation['is_visible'],
        ];

        if ($model->update($id, $data)) {
            return redirect()->to('/messages/view/' . $id)->with('success', 'Mensagem atualizada!');
        }

        return redirect()->back()->withInput()->with('error', 'Erro ao atualizar mensagem');
    }
    
    public function send($id) {
        $model = new MessageModel();
        $message = $model->find($id);
        
        if (!$message) {
            return $this->response->setJSON(['success' => false, 'error' => 'Mensagem não encontrada']);
        }
        
        // Validar opt-out
        if (!$message['has_optout_link'] || !$message['optout_link_visible']) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Link de opt-out ausente ou invisível'
            ]);
        }
        
        // Obter contatos
        $contactIds = $this->request->getPost('contact_ids');
        
        if (empty($contactIds)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Nenhum contato selecionado']);
        }
        
        // Adicionar à fila
        $queue = new QueueManager();
        $result = $queue->queueMessage($id, $contactIds, 0);
        
        // Atualizar status
        $model->update($id, [
            'status' => 'sending',
            'total_recipients' => count($contactIds),
        ]);
        
        return $this->response->setJSON([
            'success' => true,
            'queued' => $result['queued']
        ]);
    }
    
    public function duplicate($id) {
        $model = new MessageModel();
        $message = $model->find($id);
        
        if (!$message) {
            return redirect()->back()->with('error', 'Mensagem não encontrada');
        }
        
        unset($message['id']);
        $message['subject'] = '[CÓPIA] ' . $message['subject'];
        $message['status'] = 'draft';
        $message['total_sent'] = 0;
        $message['total_opens'] = 0;
        $message['total_clicks'] = 0;
        
        $newId = $model->insert($message);
        
        return redirect()->to('/messages/edit/' . $newId)->with('success', 'Mensagem duplicada!');
    }
    
    public function cancel($id) {
        $model = new MessageModel();
        
        if ($model->update($id, ['status' => 'cancelled'])) {
            return redirect()->back()->with('success', 'Envio cancelado!');
        }
        
        return redirect()->back()->with('error', 'Erro ao cancelar');
    }
    
    public function reschedule($id) {
        $model = new MessageModel();
        $scheduledAt = $this->request->getPost('scheduled_at');
        
        if ($model->update($id, ['scheduled_at' => $scheduledAt, 'status' => 'scheduled'])) {
            return redirect()->back()->with('success', 'Reagendado com sucesso!');
        }
        
        return redirect()->back()->with('error', 'Erro ao reagendar');
    }
    
    protected function validateOptOutLink(string $html): array {
        // Verifica presença de link de opt-out
        $hasOptout = (
            stripos($html, '{{optout_link}}') !== false ||
            stripos($html, '{{unsubscribe_link}}') !== false ||
            stripos($html, 'href="' . base_url('optout/') . '"') !== false
        );
        
        if (!$hasOptout) {
            return [
                'valid' => false,
                'has_optout' => false,
                'is_visible' => false,
                'message' => 'Link de opt-out não encontrado. Use {{optout_link}} no HTML.'
            ];
        }
        
        // Verifica visibilidade (não está escondido)
        $isVisible = true;
        
        // Verifica se está dentro de elemento com display:none
        if (preg_match('/style=["\']([^"\']*)display:\s*none([^"\']*)["\']/i', $html, $matches)) {
            $styleBlock = $matches[0];
            if (stripos($styleBlock, 'optout') !== false || stripos($styleBlock, 'unsubscribe') !== false) {
                $isVisible = false;
            }
        }
        
        // Verifica cor do texto igual ao fundo
        // (implementação simplificada)
        
        if (!$isVisible) {
            return [
                'valid' => false,
                'has_optout' => true,
                'is_visible' => false,
                'message' => 'Link de opt-out está escondido (display:none ou cor invisível)'
            ];
        }
        
        return [
            'valid' => true,
            'has_optout' => true,
            'is_visible' => true,
            'message' => 'OK'
        ];
    }
    
    protected function saveResendConfig($messageId) {
        $resends = $this->request->getPost('resends');
        
        if (empty($resends)) {
            return;
        }
        
        $db = \Config\Database::connect();
        
        foreach ($resends as $resend) {
            if (!empty($resend['hours_after']) && !empty($resend['subject'])) {
                $db->table('message_resends')->insert([
                    'message_id' => $messageId,
                    'resend_number' => $resend['number'],
                    'hours_after' => $resend['hours_after'],
                    'subject' => $resend['subject'],
                    'is_active' => 1,
                ]);
            }
        }
    }
}
