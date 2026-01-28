<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MessageModel;
use App\Models\CampaignModel;
use App\Models\SenderModel;
use App\Models\MessageSendModel;
use App\Models\ContactModel;
use App\Models\ContactListModel;
use App\Libraries\Email\QueueManager;
use CodeIgniter\I18n\Time;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RedirectResponse;

class MessageController extends BaseController {
    /**
     * Fuso horário padrão configurado para os agendamentos.
     *
     * @var string
     */
    protected string $appTimezone = 'America/Sao_Paulo';

    public function index(): string {
        $model = new MessageModel();
        $messages = $model->orderBy('created_at', 'DESC')->paginate(20);

        $sendModel = new MessageSendModel();
        $stats = $sendModel
            ->select('message_id, COUNT(*) AS total, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) AS sent')
            ->groupBy('message_id')
            ->findAll();

        $messageProgress = [];

        foreach ($stats as $stat) {
            $total = (int) ($stat['total'] ?? 0);
            $sent = (int) ($stat['sent'] ?? 0);

            $messageProgress[(int) $stat['message_id']] = [
                'total' => $total,
                'sent' => $sent,
                'percentage' => $total > 0 ? round(($sent / $total) * 100) : 0,
            ];
        }

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
            'messageProgress' => $messageProgress,
            'activeMenu' => 'messages',
            'pageTitle' => 'Mensagens'
        ]);
    }
    
    public function create(): string {
        $campaignModel = new CampaignModel();
        $senderModel = new SenderModel();
        $contactListModel = new ContactListModel();

        $defaultCampaignId = (int) $this->request->getGet('campaign_id');

        return view('messages/detail', [
            'campaigns' => $campaignModel->where('is_active', 1)->findAll(),
            'senders' => $senderModel->where('is_active', 1)->where('ses_verified', 1)->findAll(),
            'contactLists' => $contactListModel->orderBy('name', 'ASC')->findAll(),
            'message' => [],
            'selectedLists' => [],
            'activeMenu' => 'messages',
            'pageTitle' => 'Nova Mensagem',
            'selectedCampaignId' => $defaultCampaignId > 0 ? $defaultCampaignId : null,
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
        $campaignModel = new CampaignModel();
        $senderModel = new SenderModel();

        $sends = $sendModel->where('message_id', $id)
            ->orderBy('id', 'DESC')
            ->findAll(20);

        $campaignName = '';
        if (!empty($message['campaign_id'])) {
            $campaign = $campaignModel->find($message['campaign_id']);
            $campaignName = $campaign['name'] ?? '';
        }
        
        $senderEmail = '';
        if (!empty($message['sender_id'])) {
            $sender = $senderModel->find($message['sender_id']);
            $senderEmail = $sender['email'] ?? '';
        }

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
            'campaignName' => $campaignName,
            'senderEmail' => $senderEmail,
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

        // Obter permissões de edição
        $permissions = $this->getEditPermissions($message);
        
        // Se não pode editar de forma alguma
        if ($permissions['edit_mode'] === 'none' && !$permissions['show_draft_prompt']) {
            return redirect()->to('/messages/view/' . $id)
                ->with('error', $permissions['reason']);
        }

        $campaignModel = new CampaignModel();
        $senderModel = new SenderModel();
        $contactListModel = new ContactListModel();

        $preselectedLists = $this->getPreselectedLists($id);
        $canEditRecipients = $this->canModifyRecipients($message);
        $resendRules = $this->getResendRules($id);
        $resendLocks = [];

        foreach ($resendRules as $rule) {
            $resendLocks[(int) $rule['id']] = $this->hasQueuedResend($id, (int) $rule['resend_number']);
        }

        return view('messages/detail', [
            'message' => $message,
            'campaigns' => $campaignModel->where('is_active', 1)->findAll(),
            'senders' => $senderModel->where('is_active', 1)->where('ses_verified', 1)->findAll(),
            'resendRules' => $resendRules,
            'contactLists' => $contactListModel->orderBy('name', 'ASC')->findAll(),
            'selectedLists' => $preselectedLists,
            'activeMenu' => 'messages',
            'pageTitle' => 'Editar Mensagem',
            'selectedCampaignId' => null,
            'editPermissions' => $permissions,
        ]);
    }

    /**
     * Persiste uma nova mensagem e agenda os envios.
     *
     * @return ResponseInterface
     */
    public function store(): ResponseInterface {
        try {
            $model = new MessageModel();

            $messageId = (int) ($this->request->getPost('message_id') ?? 0);

            // Validar opt-out link
            $htmlContent = $this->sanitizeHtmlContent($this->request->getPost('html_content'));
            
            if (empty(trim($htmlContent))) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'O conteúdo do email não pode estar vazio.'
                ]);
            }
            
            $validation = $this->validateOptOutLink($htmlContent);

        if (!$validation['valid']) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $validation['message']
            ]);
        }

        $scheduledAt = $this->normalizeScheduleInput($this->request->getPost('scheduled_at'));
        $contactLists = (array) $this->request->getPost('contact_lists');

        if (empty($contactLists) && $messageId > 0) {
            $contactLists = $this->getDraftContactLists($messageId);
        }

        if (empty($contactLists)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Selecione ao menos uma lista de contatos para agendar o envio.',
            ]);
        }

        $contactIds = $this->getContactsFromLists($contactLists);

        if (empty($contactIds)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Nenhum contato válido encontrado nas listas selecionadas.',
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
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt ?: $this->getCurrentDateTime(),
            'progress_data' => null,
        ];

        $resendData = (array) $this->request->getPost('resends');

        if ($messageId > 0) {
            log_message('info', "Atualizando mensagem {$messageId} com status: {$data['status']}");
            $model->update($messageId, $data);
            log_message('info', "Mensagem {$messageId} atualizada");
        } else {
            log_message('info', "Criando nova mensagem com status: {$data['status']}");
            $messageId = $model->insert($data);
            log_message('info', "Mensagem criada com ID: {$messageId}");
        }

        if ($messageId > 0) {
            $queue = new QueueManager();
            $queue->queueMessage($messageId, $contactIds, 0);

            $model->update($messageId, [
                'total_recipients' => count($contactIds),
            ]);

            $this->saveResendRules($messageId, $data['scheduled_at'], $resendData);

            return $this->response->setJSON([
                'success' => true,
                'message_id' => $messageId
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'error' => 'Erro ao salvar mensagem'
        ]);
        } catch (\Exception $e) {
            log_message('error', 'Erro ao salvar mensagem: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Erro ao salvar mensagem: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Salva o progresso da criação de mensagem etapa a etapa.
     *
     * @return ResponseInterface
     */
    public function saveProgress(): ResponseInterface
    {
        try {
            $model = new MessageModel();
        $messageId = (int) ($this->request->getPost('message_id') ?? 0);
        $current = $messageId > 0 ? $model->find($messageId) : null;

        $step = (int) ($this->request->getPost('step') ?? 1);
        $htmlContent = $this->sanitizeHtmlContent($this->request->getPost('html_content') ?? '');
        $validation = $this->validateStepData($step, $htmlContent);

        if (!$validation['valid']) 
        {
            return $this->response->setJSON([
                'success' => false,
                'error' => $validation['message'],
            ]);
        }

        $data = $this->collectStepFields($step, $htmlContent, $validation);

        if ($current) {
            $data = array_filter($data, static fn ($value) => $value !== null);
            $model->update((int) $current['id'], $data);
            $messageId = (int) $current['id'];
        } else {
            $messageId = $model->insert($data);
        }

        if ($messageId <= 0) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Não foi possível salvar o progresso da mensagem.',
            ]);
        }

        $this->storeStepProgressData($messageId, $step);

        return $this->response->setJSON([
            'success' => true,
            'message_id' => $messageId,
        ]);
        } catch (\Exception $e) {
            log_message('error', 'Erro ao salvar progresso: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Erro ao salvar progresso: ' . $e->getMessage()
            ]);
        }
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

        // Obter permissões de edição
        $permissions = $this->getEditPermissions($message);
        
        // Se não pode editar
        if (!$permissions['can_edit']) {
            return redirect()->to('/messages/view/' . $id)
                ->with('error', $permissions['reason']);
        }
        
        // Se modo de edição é apenas reenvios
        if ($permissions['edit_mode'] === 'resend_only') {
            // Apenas permitir edição de reenvios
            $resends = (array) $this->request->getPost('resends');
            
            if (empty($resends)) {
                return redirect()->back()->with('error', 'Nenhum reenvio para atualizar.');
            }
            
            $this->rescheduleResends($id, $resends, $message['subject']);
            
            return redirect()->to('/messages/view/' . $id)
                ->with('success', 'Reenvios atualizados com sucesso!');
        }
        
        // Modo de edição completa
        $htmlContent = $this->sanitizeHtmlContent($this->request->getPost('html_content'));
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

        $scheduledAt = $this->normalizeScheduleInput($this->request->getPost('scheduled_at'));
        $resends = (array) $this->request->getPost('resends');
        $contactLists = (array) $this->request->getPost('contact_lists');

        $canUpdateRecipients = $this->canModifyRecipients($message) && $this->request->getPost('contact_lists') !== null;

        if ($this->canRescheduleMessage($message) && !empty($scheduledAt)) {
            $data['scheduled_at'] = $scheduledAt;
            $data['status'] = 'scheduled';
        }

        $model->update($id, $data);

        if ($canUpdateRecipients) {
            if (empty($contactLists)) {
                return redirect()->back()->withInput()->with('error', 'Selecione ao menos uma lista de contatos para reagendar.');
            }

            $contactIds = $this->getContactsFromLists($contactLists);

            if (empty($contactIds)) {
                return redirect()->back()->withInput()->with('error', 'Nenhum contato válido encontrado nas listas selecionadas.');
            }

            $this->refreshMessageRecipients($id, $contactIds);

            $model->update($id, [
                'total_recipients' => count($contactIds),
                'total_sent' => 0,
            ]);
        }

        $this->rescheduleResends($id, $resends, $data['subject'] ?? $message['subject']);

        return redirect()->to('/messages/view/' . $id)->with('success', 'Mensagem atualizada!');
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
        $message['scheduled_at'] = null;
        $message['sent_at'] = null;

        $newId = $model->insert($message);

        return redirect()->to('/messages/edit/' . $newId)->with('success', 'Mensagem duplicada!');
    }

    /**
     * Remove uma mensagem rascunho ou agendada junto aos seus envios e reenvios.
     *
     * @param int $id ID da mensagem
     *
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        $model = new MessageModel();
        $message = $model->find($id);

        if (!$message) {
            return redirect()->to('/messages')->with('error', 'Mensagem não encontrada');
        }

        if (!in_array($message['status'], ['draft', 'scheduled'], true)) {
            return redirect()->back()->with('error', 'Somente rascunhos ou mensagens agendadas podem ser excluídas.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $db->table('message_sends')->where('message_id', $id)->delete();
        $db->table('resend_rules')->where('message_id', $id)->delete();
        $model->delete($id);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Não foi possível excluir a mensagem.');
        }

        return redirect()->to('/messages')->with('success', 'Mensagem excluída com sucesso.');
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
        $scheduledAt = $this->normalizeScheduleInput($this->request->getPost('scheduled_at'));

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
    
    /**
     * Calcula e persiste regras de reenvio vinculadas à mensagem.
     *
     * @param int         $messageId        ID da mensagem
     * @param string|null $firstScheduledAt Data e hora do primeiro disparo
     * @param array|null  $resendPayload    Dados de reenvio enviados pelo formulário
     */
    protected function saveResendRules(int $messageId, ?string $firstScheduledAt, ?array $resendPayload = null): void
    {
        $resends = $resendPayload ?? $this->request->getPost('resends');

        if (empty($resends)) {
            return;
        }

        $db = \Config\Database::connect();
        $defaultSubject = (string) $this->request->getPost('subject');

        foreach ($resends as $resend) {
            $scheduledAt = $this->normalizeScheduleInput($resend['scheduled_at'] ?? '');

            if (empty($scheduledAt)) {
                continue;
            }

            $subject = trim((string) ($resend['subject'] ?? ''));
            $subjectOverride = $subject !== '' ? $subject : null;

            $db->table('resend_rules')->insert([
                'message_id' => $messageId,
                'resend_number' => (int) $resend['number'],
                'hours_after' => 0,
                'subject_override' => $subjectOverride ?? $defaultSubject,
                'status' => 'pending',
                'scheduled_at' => $scheduledAt,
            ]);
        }
    }

    /**
     * Valida dados obrigatórios de acordo com a etapa do assistente.
     *
     * @param int    $step        Etapa atual
     * @param string $htmlContent Conteúdo HTML recebido
     * @return array<string, mixed>
     */
    protected function validateStepData(int $step, string $htmlContent): array
    {
        $required = [
            'campaign_id' => 'Campanha é obrigatória.',
            'sender_id' => 'Remetente é obrigatório.',
            'subject' => 'Assunto é obrigatório.',
            'from_name' => 'Nome do remetente é obrigatório.',
        ];

        foreach ($required as $field => $message) {
            if (trim((string) $this->request->getPost($field)) === '') {
                return ['valid' => false, 'message' => $message];
            }
        }

        if (in_array($step, [2, 3], true)) {
            
            if (trim(strip_tags($htmlContent)) === '') {
                return [
                    'valid' => false,
                    'message' => 'Preencha o conteúdo da mensagem antes de avançar.',
                ];
            }

            $validation = $this->validateOptOutLink($htmlContent);

            if (!$validation['valid']) {
                return $validation;
            }

            return $validation;
        }

        if ($step === 4) {
            $contactLists = (array) $this->request->getPost('contact_lists');

            if (empty($contactLists)) {
                return [
                    'valid' => false,
                    'message' => 'Selecione ao menos uma lista de contatos para continuar.',
                ];
            }
        }

        if ($step === 5) {
            $scheduledAt = $this->normalizeScheduleInput($this->request->getPost('scheduled_at'));

            if (empty($scheduledAt)) {
                return [
                    'valid' => false,
                    'message' => 'Informe uma data e hora válidas para agendamento.',
                ];
            }
        }

        return ['valid' => true, 'message' => 'OK'];
    }

    /**
     * Remove atributos de largura e altura das imagens para evitar distorções e preserva placeholders.
     *
     * @param string|null $htmlContent Conteúdo HTML recebido do formulário.
     *
     * @return string HTML saneado sem os atributos width e height.
     */
    protected function sanitizeHtmlContent(?string $htmlContent): string
    {
        $content = $htmlContent ?? '';

        if (trim($content) === '') {
            return '';
        }

        $cleanedContent = preg_replace_callback(
            '/<img\b[^>]*?>/i',
            static function (array $matches): string {
                $imageTag = $matches[0];

                $imageTag = preg_replace(
                    '/\s+(width|height)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i',
                    '',
                    $imageTag
                );

                return preg_replace('/\s{2,}/', ' ', trim($imageTag));
            },
            $content
        );

        return $cleanedContent ?? $content;
    }

    /**
     * Monta os campos para salvar conforme a etapa atual.
     *
     * @param int   $step        Etapa atual
     * @param string $htmlContent Conteúdo HTML recebido
     * @param array $validation  Resultado da validação
     * @return array<string, mixed>
     */
    protected function collectStepFields(int $step, string $htmlContent, array $validation): array
    {
        $data = [
            'status' => 'draft',
            'campaign_id' => $this->request->getPost('campaign_id'),
            'sender_id' => $this->request->getPost('sender_id'),
            'subject' => $this->request->getPost('subject'),
            'from_name' => $this->request->getPost('from_name'),
            'reply_to' => $this->request->getPost('reply_to'),
            'html_content' => $htmlContent ?? '',
            'has_optout_link' => $validation['has_optout'] ?? false,
            'optout_link_visible' => $validation['is_visible'] ?? false,
        ];

        if ($step === 5) {
            $data['scheduled_at'] = $this->normalizeScheduleInput($this->request->getPost('scheduled_at'));
        }

        return $data;
    }

    /**
     * Persiste dados adicionais da etapa atual dentro de progress_data.
     *
     * @param int $messageId ID da mensagem
     * @param int $step      Etapa atual
     * @return void
     */
    protected function storeStepProgressData(int $messageId, int $step): void
    {
        $model = new MessageModel();
        $message = $model->find($messageId);

        if (!$message) {
            return;
        }

        $progress = $this->decodeProgressData($message['progress_data'] ?? null);
        $needsUpdate = false;

        // Step 4: Destinatários
        if ($step === 4) {
            $contactLists = (array) $this->request->getPost('contact_lists');
            $progress['contact_lists'] = array_values(array_filter(array_map('intval', $contactLists)));
            $needsUpdate = true;
        }

        // Step 5: Envios (Agendamento + Reenvios)
        if ($step === 5) {
            $scheduledAt = $this->request->getPost('scheduled_at');
            if ($scheduledAt) {
                $progress['scheduled_at'] = $scheduledAt;
                $needsUpdate = true;
            }
            
            $resends = $this->request->getPost('resends');
            if ($resends) {
                $progress['resends'] = $resends;
                $needsUpdate = true;
            }
        }

        if ($needsUpdate) {
            $model->update($messageId, [
                'progress_data' => json_encode($progress, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * Decodifica o JSON de progresso salvo.
     *
     * @param string|null $payload Conteúdo em JSON
     * @return array<string, mixed>
     */
    protected function decodeProgressData(?string $payload): array
    {
        if (empty($payload)) {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Retorna IDs de contatos ativos pertencentes às listas selecionadas.
     *
     * @param array $contactLists IDs das listas selecionadas
     *
     * @return array
     */
    protected function getContactsFromLists(array $contactLists): array
    {
        $contactModel = new ContactModel();

        $builder = $contactModel->builder();
        $builder->select('contacts.id')
            ->join('contact_list_members', 'contact_list_members.contact_id = contacts.id')
            ->whereIn('contact_list_members.list_id', $contactLists)
            ->where('contacts.is_active', 1)
            ->where('contacts.opted_out', 0)
            ->where('contacts.bounced', 0)
            ->groupBy('contacts.id');

        $result = $builder->get()->getResultArray();

        return array_column($result, 'id');
    }

    /**
     * Recupera listas de contatos salvas como rascunho.
     *
     * @param int $messageId ID da mensagem
     * @return array<int>
     */
    protected function getDraftContactLists(int $messageId): array
    {
        $model = new MessageModel();
        $message = $model->find($messageId);

        $progress = $this->decodeProgressData($message['progress_data'] ?? null);

        return array_map('intval', $progress['contact_lists'] ?? []);
    }

    /**
     * Recupera regras de reenvio existentes para edição.
     *
     * @param int $messageId ID da mensagem
     *
     * @return array
     */
    protected function getResendRules(int $messageId): array
    {
        $db = \Config\Database::connect();

        return $db->table('resend_rules')
            ->where('message_id', $messageId)
            ->orderBy('resend_number', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Verifica se a mensagem pode ser reagendada.
     *
     * @param array $message Dados da mensagem
     *
     * @return bool
     */
    protected function canRescheduleMessage(array $message): bool
    {
        if (empty($message['scheduled_at'])) {
            return false;
        }

        if (in_array($message['status'] ?? '', ['sent', 'cancelled'], true)) {
            return false;
        }

        if ($this->originalSendsPending((int) $message['id'])) {
            return true;
        }

        try {
            $timezone = $this->getAppTimezone();
            $scheduledAt = Time::parse($message['scheduled_at'], $timezone);

            return $scheduledAt->getTimestamp() > Time::now($timezone)->getTimestamp();
        } catch (\Exception $exception) {
            log_message('error', 'Falha ao avaliar reagendamento: ' . $exception->getMessage());

            return false;
        }
    }

    /**
     * Atualiza datas de reenvio para regras ainda pendentes.
     *
     * @param int    $messageId      ID da mensagem
     * @param array  $resends        Dados enviados pelo formulário
     * @param string $defaultSubject Assunto original da mensagem
     *
     * @return void
     */
    protected function rescheduleResends(int $messageId, array $resends, string $defaultSubject): void
    {
        if (empty($resends)) {
            return;
        }

        $db = \Config\Database::connect();

        $timezone = $this->getAppTimezone();
        $now = Time::now($timezone);

        foreach ($resends as $ruleId => $resend) {
            $newSchedule = $this->normalizeScheduleInput($resend['scheduled_at'] ?? '');

            if (empty($newSchedule)) {
                continue;
            }

            $current = $db->table('resend_rules')
                ->where('id', (int) $ruleId)
                ->where('message_id', $messageId)
                ->get()
                ->getRowArray();

            if (!$current) {
                continue;
            }

            if ($current['status'] !== 'pending') {
                continue;
            }

            if ($this->hasQueuedResend($messageId, (int) $current['resend_number'])) {
                continue;
            }

            $newScheduled = Time::parse($newSchedule, $timezone);

            if ($newScheduled->getTimestamp() < $now->getTimestamp()) {
                continue;
            }

            $subject = trim((string) ($resend['subject'] ?? ''));
            $subjectOverride = $subject !== '' ? $subject : $defaultSubject;

            $db->table('resend_rules')
                ->where('id', (int) $ruleId)
                ->update([
                    'scheduled_at' => $newSchedule,
                    'subject_override' => $subjectOverride,
                ]);
        }
    }

    /**
     * Obtém o fuso horário configurado para a aplicação.
     *
     * @return string
     */
    protected function getAppTimezone(): string
    {
        return config('App')->appTimezone ?? $this->appTimezone;
    }

    /**
     * Converte a entrada de data/hora para string padronizada no fuso de São Paulo.
     *
     * @param string|null $dateTime Valor bruto recebido do formulário
     *
     * @return string|null
     */
    protected function normalizeScheduleInput(?string $dateTime): ?string
    {
        if (empty($dateTime)) {
            return null;
        }

        try {
            return Time::parse($dateTime, $this->getAppTimezone())->toDateTimeString();
        } catch (\Exception $exception) {
            log_message('error', 'Data/hora inválida informada: ' . $exception->getMessage());

            return null;
        }
    }

    /**
     * Recupera a data/hora atual no fuso de São Paulo.
     *
     * @return string
     */
    protected function getCurrentDateTime(): string
    {
        return Time::now($this->getAppTimezone())->toDateTimeString();
    }

    /**
     * Indica se a mensagem permite alterar destinatários e horários.
     *
     * @param array $message Dados da mensagem
     * @return bool
     */
    protected function canModifyRecipients(array $message): bool
    {
        if (empty($message['id'])) {
            return false;
        }

        if (in_array($message['status'] ?? '', ['sent', 'cancelled'], true)) {
            return false;
        }

        return $this->originalSendsPending((int) $message['id']);
    }

    /**
     * Verifica se os envios originais ainda estão pendentes.
     *
     * @param int $messageId ID da mensagem
     * @return bool
     */
    protected function originalSendsPending(int $messageId): bool
    {
        $sendModel = new MessageSendModel();

        $nonPending = $sendModel
            ->where('message_id', $messageId)
            ->where('resend_number', 0)
            ->where('status !=', 'pending')
            ->countAllResults();

        return $nonPending === 0;
    }

    /**
     * Recria os destinatários de uma mensagem ainda não enviada.
     *
     * @param int   $messageId  ID da mensagem
     * @param array $contactIds IDs dos contatos permitidos
     * @return void
     */
    protected function refreshMessageRecipients(int $messageId, array $contactIds): void
    {
        $sendModel = new MessageSendModel();

        $sendModel->where('message_id', $messageId)->delete();

        $queue = new QueueManager();
        $queue->queueMessage($messageId, $contactIds, 0);
    }

    /**
     * Calcula listas que cobrem integralmente os destinatários atuais.
     *
     * @param int $messageId ID da mensagem
     * @return array<int>
     */
    protected function getPreselectedLists(int $messageId): array
    {
        $db = \Config\Database::connect();

        $rows = $db->table('contact_lists')
            ->select('contact_lists.id, contact_lists.total_contacts, COUNT(DISTINCT message_sends.contact_id) AS recipients')
            ->join('contact_list_members', 'contact_list_members.list_id = contact_lists.id')
            ->join('message_sends', 'message_sends.contact_id = contact_list_members.contact_id')
            ->where('message_sends.message_id', $messageId)
            ->where('message_sends.resend_number', 0)
            ->groupBy('contact_lists.id, contact_lists.total_contacts')
            ->get()
            ->getResultArray();

        $preselected = [];

        foreach ($rows as $row) {
            if ((int) ($row['total_contacts'] ?? 0) > 0 && (int) $row['total_contacts'] === (int) $row['recipients']) {
                $preselected[] = (int) $row['id'];
            }
        }

        return $preselected;
    }

    /**
     * Quebra destinatários atuais por lista de origem.
     *
     * @param int $messageId ID da mensagem
     * @return array<int, array<string, mixed>>
     */
    protected function getRecipientListBreakdown(int $messageId): array
    {
        $db = \Config\Database::connect();

        return $db->table('contact_lists')
            ->select('contact_lists.id, contact_lists.name, COUNT(DISTINCT message_sends.contact_id) AS recipients')
            ->join('contact_list_members', 'contact_list_members.list_id = contact_lists.id')
            ->join('message_sends', 'message_sends.contact_id = contact_list_members.contact_id')
            ->where('message_sends.message_id', $messageId)
            ->where('message_sends.resend_number', 0)
            ->groupBy('contact_lists.id, contact_lists.name')
            ->orderBy('contact_lists.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Total de destinatários atuais da mensagem.
     *
     * @param int $messageId ID da mensagem
     * @return int
     */
    protected function countMessageRecipients(int $messageId): int
    {
        $sendModel = new MessageSendModel();

        return $sendModel
            ->where('message_id', $messageId)
            ->where('resend_number', 0)
            ->countAllResults();
    }

    /**
     * Verifica se um reenvio já possui fila criada.
     *
     * @param int $messageId ID da mensagem
     * @param int $resendNumber Número do reenvio
     * @return bool
     */
    protected function hasQueuedResend(int $messageId, int $resendNumber): bool
    {
        $sendModel = new MessageSendModel();

        return $sendModel
            ->where('message_id', $messageId)
            ->where('resend_number', $resendNumber)
            ->countAllResults() > 0;
    }

    /**
     * Determina as permissões de edição para uma mensagem.
     *
     * @param array $message Dados da mensagem
     * @return array [
     *   'can_edit' => bool,
     *   'edit_mode' => 'full'|'resend_only'|'none',
     *   'show_draft_prompt' => bool,
     *   'time_until_send' => int (segundos),
     *   'reason' => string
     * ]
     */
    protected function getEditPermissions(array $message): array
    {
        $timezone = $this->getAppTimezone();
        $now = Time::now($timezone);
        
        // Verificar se todos os envios (original + 3 reenvios) já passaram
        if ($this->allSendsCompleted($message)) {
            return [
                'can_edit' => false,
                'edit_mode' => 'none',
                'show_draft_prompt' => false,
                'time_until_send' => 0,
                'reason' => 'Todos os envios foram concluídos'
            ];
        }
        
        // Verificar se o primeiro envio já passou
        if ($this->firstSendPassed($message)) {
            return [
                'can_edit' => true,
                'edit_mode' => 'resend_only',
                'show_draft_prompt' => false,
                'time_until_send' => 0,
                'reason' => 'Primeiro envio já realizado, apenas reenvios podem ser editados'
            ];
        }
        
        // Mensagem agendada
        if ($message['status'] === 'scheduled' && !empty($message['scheduled_at'])) {
            try {
                $scheduledTime = Time::parse($message['scheduled_at'], $timezone);
                $timeUntilSend = $scheduledTime->getTimestamp() - $now->getTimestamp();
                
                // Menos de 1 minuto: mostrar prompt para transformar em rascunho
                if ($timeUntilSend < 60 && $timeUntilSend > 0) {
                    return [
                        'can_edit' => false,
                        'edit_mode' => 'none',
                        'show_draft_prompt' => true,
                        'time_until_send' => $timeUntilSend,
                        'reason' => 'Envio agendado para menos de 1 minuto'
                    ];
                }
                
                // Agendado mas já passou: não permitir edição
                if ($timeUntilSend <= 0) {
                    return [
                        'can_edit' => false,
                        'edit_mode' => 'none',
                        'show_draft_prompt' => false,
                        'time_until_send' => 0,
                        'reason' => 'Data de envio já passou'
                    ];
                }
                
                // Agendado com mais de 1 minuto: bloquear edição
                return [
                    'can_edit' => false,
                    'edit_mode' => 'none',
                    'show_draft_prompt' => false,
                    'time_until_send' => $timeUntilSend,
                    'reason' => 'Mensagem agendada não pode ser editada'
                ];
            } catch (\Exception $e) {
                log_message('error', 'Erro ao calcular permissões de edição: ' . $e->getMessage());
            }
        }
        
        // Rascunho ou outros status: edição completa
        return [
            'can_edit' => true,
            'edit_mode' => 'full',
            'show_draft_prompt' => false,
            'time_until_send' => 0,
            'reason' => 'Mensagem pode ser editada livremente'
        ];
    }
    
    /**
     * Verifica se o primeiro envio já foi realizado.
     *
     * @param array $message Dados da mensagem
     * @return bool
     */
    protected function firstSendPassed(array $message): bool
    {
        if (empty($message['scheduled_at'])) {
            return false;
        }
        
        try {
            $timezone = $this->getAppTimezone();
            $scheduledTime = Time::parse($message['scheduled_at'], $timezone);
            $now = Time::now($timezone);
            
            return $scheduledTime->getTimestamp() < $now->getTimestamp();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Verifica se todos os envios (original + 3 reenvios) já foram concluídos.
     *
     * @param array $message Dados da mensagem
     * @return bool
     */
    protected function allSendsCompleted(array $message): bool
    {
        // Verificar se o primeiro envio passou
        if (!$this->firstSendPassed($message)) {
            return false;
        }
        
        // Buscar todas as regras de reenvio
        $db = \Config\Database::connect();
        $resendRules = $db->table('resend_rules')
            ->where('message_id', $message['id'])
            ->orderBy('scheduled_at', 'DESC')
            ->get()
            ->getResultArray();
        
        // Se não há reenvios, considerar completo se o primeiro envio passou
        if (empty($resendRules)) {
            return true;
        }
        
        // Verificar se todos os reenvios já passaram
        $timezone = $this->getAppTimezone();
        $now = Time::now($timezone);
        
        foreach ($resendRules as $rule) {
            if (empty($rule['scheduled_at'])) {
                // Se há reenvio sem data, não está completo
                return false;
            }
            
            try {
                $resendTime = Time::parse($rule['scheduled_at'], $timezone);
                if ($resendTime->getTimestamp() >= $now->getTimestamp()) {
                    // Há pelo menos um reenvio futuro
                    return false;
                }
            } catch (\Exception $e) {
                log_message('error', 'Erro ao verificar data de reenvio: ' . $e->getMessage());
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Transforma mensagem agendada em rascunho.
     * Usado quando usuário confirma edição de mensagem agendada para menos de 1 minuto.
     *
     * @param int $messageId ID da mensagem
     * @return bool
     */
    public function convertToDraft(int $messageId): RedirectResponse
    {
        $model = new MessageModel();
        $message = $model->find($messageId);
        
        if (!$message) {
            return redirect()->to('/messages')->with('error', 'Mensagem não encontrada');
        }
        
        // Verificar se está agendada
        if ($message['status'] !== 'scheduled') {
            return redirect()->to('/messages/edit/' . $messageId)
                ->with('error', 'Apenas mensagens agendadas podem ser transformadas em rascunho');
        }
        
        // Transformar em rascunho
        $model->update($messageId, [
            'status' => 'draft',
            'scheduled_at' => null,
        ]);
        
        // Remover filas de envio pendentes
        $sendModel = new MessageSendModel();
        $sendModel->where('message_id', $messageId)
            ->where('status', 'pending')
            ->delete();
        
        // Remover regras de reenvio
        $db = \Config\Database::connect();
        $db->table('resend_rules')
            ->where('message_id', $messageId)
            ->delete();
        
        return redirect()->to('/messages/edit/' . $messageId)
            ->with('success', 'Mensagem transformada em rascunho. Você pode editá-la agora.');
    }
}
