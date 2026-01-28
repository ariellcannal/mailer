<?php

namespace App\Libraries\Email;

use App\Libraries\AWS\BounceNotificationService;
use App\Libraries\AWS\SESService;
use App\Models\MessageSendModel;
use App\Models\ContactModel;
use App\Models\MessageModel;
use CodeIgniter\I18n\Time;

/**
 * Queue Manager
 * 
 * Gerencia fila de envio de emails em massa
 * 
 * @package App\Libraries\Email
 * @author  Mailer System
 * @version 1.0.0
 */
class QueueManager
{
    /**
     * @var SESService
     */
    protected $sesService;

    /**
     * @var BounceNotificationService
     */
    protected BounceNotificationService $bounceService;

    /**
     * @var MessageSendModel
     */
    protected $sendModel;

    /**
     * @var ContactModel
     */
    protected $contactModel;

    /**
     * @var MessageModel
     */
    protected $messageModel;

    /**
     * Fuso horário padrão utilizado para os agendamentos.
     *
     * @var string
     */
    protected string $timezone = 'America/Sao_Paulo';

    /**
     * Taxa de throttling (emails por segundo)
     * 
     * @var int
     */
    protected int $throttleRate = 14;

    /**
     * Construtor
     */
    public function __construct()
    {        
        $this->sendModel = new MessageSendModel();
        $this->contactModel = new ContactModel();
        $this->messageModel = new MessageModel();

        $this->timezone = config('App')->appTimezone ?? $this->timezone;

        $this->throttleRate = (int) getenv('app.throttleRate') ?: 14;
    }
    
    /**
     * Inicializa o serviço SES (lazy loading)
     * 
     * @return SESService
     * @throws \Exception Se credenciais não estiverem configuradas
     */
    protected function getSESService(): SESService
    {
        if ($this->sesService === null) {
            $this->sesService = new SESService();
        }
        return $this->sesService;
    }
    
    /**
     * Inicializa o serviço de bounce (lazy loading)
     * 
     * @return BounceNotificationService
     * @throws \Exception Se credenciais não estiverem configuradas
     */
    protected function getBounceService(): BounceNotificationService
    {
        if ($this->bounceService === null) {
            $this->bounceService = new BounceNotificationService();
        }
        return $this->bounceService;
    }

    /**
     * Adiciona mensagem à fila de envio
     * 
     * @param int   $messageId ID da mensagem
     * @param array $contactIds IDs dos contatos destinatários
     * @param int   $resendNumber Número do reenvio (0 = original)
     * 
     * @return array Resultado da operação
     */
    public function queueMessage(int $messageId, array $contactIds, int $resendNumber = 0): array
    {
        $queued = 0;
        $errors = [];

        foreach ($contactIds as $contactId) {
            try {
                // Gera hash único para tracking
                $trackingHash = $this->generateTrackingHash($messageId, $contactId, $resendNumber);

                // Insere na fila
                $this->sendModel->insert([
                    'message_id' => $messageId,
                    'contact_id' => $contactId,
                    'resend_number' => $resendNumber,
                    'tracking_hash' => $trackingHash,
                    'opened' => 0,
                    'total_opens' => 0,
                    'clicked' => 0,
                    'total_clicks' => 0,
                    'status' => 'pending',
                ]);

                $queued++;
            } catch (\Exception $e) {
                $errors[] = [
                    'contact_id' => $contactId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'queued' => $queued,
            'errors' => $errors,
        ];
    }

    /**
     * Processa fila de envio
     * 
     * @param int $batchSize Tamanho do lote
     * 
     * @return array Resultado do processamento
     */
    public function processQueue(int $batchSize = 100): array
    {
        $now = $this->now();

        $this->queueResendsDue($now);

        $this->finalizeMessageStatuses($this->collectFinishedMessages());

        // Busca envios pendentes respeitando agendamentos
        $pending = $this->sendModel
            ->select('message_sends.*, messages.scheduled_at, messages.status AS message_status, resend_rules.subject_override')
            ->join('messages', 'messages.id = message_sends.message_id')
            ->join('resend_rules', 'resend_rules.message_id = message_sends.message_id AND resend_rules.resend_number = message_sends.resend_number', 'left')
            ->where('message_sends.status', 'pending')
            ->groupStart()
                ->where('message_sends.resend_number', 0)
                ->orGroupStart()
                    ->where('message_sends.resend_number >', 0)
                    ->groupStart()
                        ->groupStart()
                            ->where('resend_rules.status', 'pending')
                            ->where('resend_rules.scheduled_at <=', $now)
                        ->groupEnd()
                        ->orWhere('resend_rules.status', 'completed')
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
            ->groupStart()
                ->where('messages.scheduled_at <=', $now)
                ->orWhere('messages.scheduled_at', null)
            ->groupEnd()
            ->orderBy('message_sends.id', 'ASC')
            ->findAll($batchSize);

        if (empty($pending)) {
            return [
                'success' => true,
                'processed' => 0,
                'message' => 'No pending sends',
            ];
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];
        $processedMessages = [];

        foreach ($pending as $send) {
            try {
                if (($send['message_status'] ?? '') === 'scheduled') {
                    $this->messageModel->update($send['message_id'], [
                        'status' => 'sending',
                    ]);
                }

                $processedMessages[] = (int) $send['message_id'];

                // Envia email
                $result = $this->sendEmail($send);

                if ($result['success']) {
                    $sent++;

                    // Throttling
                    usleep(1000000 / $this->throttleRate); // Microsegundos
                } elseif (($result['status'] ?? '') === 'skipped') {
                    $skipped++;
                } else {
                    $failed++;
                    $errors[] = $result['error'];
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = $e->getMessage();
                log_message('error', 'Queue processing error: ' . $e->getMessage());
            }
        }

        $this->finalizeMessageStatuses($processedMessages);

        return [
            'success' => true,
            'processed' => $sent + $failed + $skipped,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Envia um email individual
     * 
     * @param array $send Dados do envio
     * 
     * @return array Resultado do envio
     */
    protected function sendEmail(array $send): array
    {
        // Busca dados da mensagem
        $message = $this->messageModel->find($send['message_id']);
        if (!$message) {
            return ['success' => false, 'error' => 'Message not found'];
        }

        // Busca dados do contato
        $contact = $this->contactModel->find($send['contact_id']);
        if (!$contact) {
            return ['success' => false, 'error' => 'Contact not found'];
        }

        // Verifica se contato está ativo
        if (!$contact['is_active'] || $contact['opted_out'] || $contact['bounced']) {
            $this->sendModel->update($send['id'], ['status' => 'cancelled']);

            return [
                'success' => false,
                'status' => 'skipped',
                'error' => 'Contact inactive',
            ];
        }

        // Prepara conteúdo do email
        $htmlBody = $this->prepareEmailContent(
            $message['html_content'],
            $contact,
            $send['tracking_hash']
        );

        // Busca dados do remetente
        $senderModel = new \App\Models\SenderModel();
        $sender = $senderModel->find($message['sender_id']);

        if (!$sender) {
            return ['success' => false, 'error' => 'Sender not found'];
        }

        if (empty($sender['bounce_flow_verified'])) {
            $flowResult = $this->getBounceService()->ensureBounceFlow($sender['domain']);

            $senderModel->update((int) $sender['id'], [
                'bounce_flow_verified' => $flowResult['success'] ? 1 : 0,
            ]);

            if (($flowResult['success'] ?? false) === false) {
                return [
                    'success' => false,
                    'error' => 'Bounce flow not configured: ' . ($flowResult['error'] ?? 'unknown error'),
                ];
            }
        }

        $emailSubject = $message['subject'];

        if (!empty($send['subject_override'])) {
            $emailSubject = $send['subject_override'];
        }

        // Envia via AWS SES
        $result = $this->getSESService()->sendEmail(
            from: $sender['email'],
            fromName: $message['from_name'],
            to: $contact['email'],
            subject: $emailSubject,
            htmlBody: $htmlBody,
            replyTo: $message['reply_to'],
            tags: [
                ['Name' => 'message_id', 'Value' => (string) $message['id']],
                ['Name' => 'campaign_id', 'Value' => (string) $message['campaign_id']],
            ]
        );

        if ($result['success']) {
            // Atualiza status do envio
            $this->sendModel->update($send['id'], [
                'status' => 'sent',
                'sent_at' => $this->now(),
            ]);

            // Atualiza contadores da mensagem
            $this->messageModel->increment($message['id'], 'total_sent');

            return ['success' => true, 'messageId' => $result['messageId']];
        } else {
            // Marca como falha
            $this->sendModel->update($send['id'], [
                'status' => 'failed',
            ]);
            
            // Verificar se é um bounce (email inválido)
            $errorMsg = strtolower($result['error'] ?? '');
            $isBounce = str_contains($errorMsg, 'address') || 
                        str_contains($errorMsg, 'mailbox') ||
                        str_contains($errorMsg, 'recipient') ||
                        str_contains($errorMsg, 'does not exist') ||
                        str_contains($errorMsg, 'invalid');
            
            if ($isBounce) {
                // Atualizar message_sends com bounce
                $this->sendModel->update($send['id'], [
                    'status' => 'bounced',
                    'bounce_type' => 'hard',
                    'bounced_at' => $this->now(),
                ]);
                
                // Registrar bounce no contato
                $this->contactModel->update($contact['id'], [
                    'bounced' => 1,
                    'bounce_type' => 'hard',
                    'is_active' => 0,  // Inativar contato em hard bounce
                ]);
                
                // Registrar bounce na tabela de bounces
                $db = \Config\Database::connect();
                $db->table('bounces')->insert([
                    'contact_id' => $contact['id'],
                    'message_id' => $message['id'],
                    'bounce_type' => 'hard',
                    'reason' => substr($result['error'], 0, 255),
                    'created_at' => $this->now(),
                ]);
                
                log_message('info', "Hard bounce registrado para contato {$contact['id']} - Contato inativado: {$result['error']}");
            }

            return ['success' => false, 'error' => $result['error']];
        }
    }

    /**
     * Prepara conteúdo do email com tracking e personalização
     * 
     * @param string $htmlContent Conteúdo HTML original
     * @param array  $contact Dados do contato
     * @param string $trackingHash Hash de tracking
     * 
     * @return string HTML preparado
     */
    protected function prepareEmailContent(string $htmlContent, array $contact, string $trackingHash): string
    {
        $nickname = $this->resolveContactNickname($contact);

        // Substitui variáveis do contato
        $htmlContent = str_replace('{{nome}}', $contact['name'] ?? '', $htmlContent);
        $htmlContent = str_replace('{{apelido}}', $contact['nickname'] ?? '', $htmlContent);
        $htmlContent = str_replace('{{email}}', $contact['email'], $htmlContent);

        // Adiciona pixel de tracking (abertura)
        $baseUrl = $this->getBaseUrl();
        $trackingPixel = '<img src="' . $baseUrl . 'track/open/' . $trackingHash . '" width="1" height="1" style="display:none;" />';
        
        // Insere pixel antes do </body>
        if (stripos($htmlContent, '</body>') !== false) {
            $htmlContent = str_ireplace('</body>', $trackingPixel . '</body>', $htmlContent);
        } else {
            $htmlContent .= $trackingPixel;
        }

        // Substitui links por links de tracking
        $htmlContent = $this->replaceLinksWithTracking($htmlContent, $trackingHash);

        // Substitui imagens para incluir o hash de tracking
        $htmlContent = $this->replaceImagesWithTracking($htmlContent, $trackingHash);

        // Substitui link de opt-out
        $optoutUrl = $baseUrl . 'optout/' . $trackingHash;
        $htmlContent = str_replace('{{optout_link}}', $optoutUrl, $htmlContent);

        // Substitui link de visualização externa
        $webviewUrl = $baseUrl . 'webview/' . $trackingHash;
        $htmlContent = str_replace('{{webview_link}}', $webviewUrl, $htmlContent);

        return $htmlContent;
    }

    /**
     * Obtém o apelido do contato com fallback para o primeiro nome.
     *
     * @param array $contact Dados do contato utilizados na personalização.
     * @return string Apelido capitalizado para substituir nas tags.
     */
    protected function resolveContactNickname(array $contact): string
    {
        $nickname = trim((string) ($contact['nickname'] ?? ''));

        if ($nickname !== '') {
            return $nickname;
        }

        $name = $contact['name'] ?? null;
        $email = (string) ($contact['email'] ?? '');

        return $this->contactModel->generateNickname($name, $email);
    }

    /**
     * Substitui links por links de tracking
     * 
     * @param string $html HTML content
     * @param string $trackingHash Hash de tracking
     * 
     * @return string HTML com links modificados
     */
    protected function replaceLinksWithTracking(string $html, string $trackingHash): string
    {
        $baseUrl = $this->getBaseUrl();

        $pattern = '/<a\b[^>]*?\bhref=(["\'])(.*?)\1[^>]*>/i';

        return preg_replace_callback($pattern, function(array $matches) use ($trackingHash, $baseUrl): string {
            $fullTag = $matches[0];
            $quote = $matches[1];
            $url = $matches[2];

            if (
                str_starts_with($url, 'mailto:') ||
                str_starts_with($url, 'tel:') ||
                str_starts_with($url, '#') ||
                str_starts_with($url, 'javascript:') ||
                str_contains($url, '{{')
            ) {
                return $fullTag;
            }

            $trackingUrl = $baseUrl . 'track/click/' . $trackingHash . '?url=' . urlencode($url);

            return preg_replace('/\bhref=(["\']).*?\1/i', 'href=' . $quote . $trackingUrl . $quote, $fullTag, 1) ?? $fullTag;
        }, $html) ?? $html;
    }

    /**
     * Acrescenta o hash de tracking às imagens utilizadas no corpo do e-mail.
     *
     * @param string $html Conteúdo HTML original.
     * @param string $trackingHash Hash de tracking do envio.
     *
     * @return string HTML atualizado com query string de tracking nas imagens.
     */
    protected function replaceImagesWithTracking(string $html, string $trackingHash): string
    {
        $pattern = '/<img\b[^>]*?\bsrc=(["\'])(.*?)\1[^>]*>/i';

        return preg_replace_callback($pattern, function(array $matches) use ($trackingHash): string {
            $fullTag = $matches[0];
            $quote = $matches[1];
            $src = $matches[2];

            if (
                str_contains($src, 'track/open/') ||
                str_starts_with(strtolower($src), 'data:') ||
                str_starts_with(strtolower($src), 'cid:') ||
                str_contains($src, '{{') ||
                ! str_contains($src, '/imagens/')
            ) {
                return $fullTag;
            }

            $updatedSrc = $this->appendTrackingHashToUrl($src, $trackingHash);

            return preg_replace('/\bsrc=(["\']).*?\1/i', 'src=' . $quote . $updatedSrc . $quote, $fullTag, 1) ?? $fullTag;
        }, $html) ?? $html;
    }

    /**
     * Anexa o hash de tracking mantendo a URL original intacta.
     *
     * @param string $url URL original da imagem.
     * @param string $trackingHash Hash de tracking do envio.
     *
     * @return string URL com a query string de tracking.
     */
    protected function appendTrackingHashToUrl(string $url, string $trackingHash): string
    {
        $parsedUrl = parse_url($url);
        $query = [];

        if (! empty($parsedUrl['query'])) {
            parse_str((string) $parsedUrl['query'], $query);
        }

        if (! array_key_exists('hash', $query)) {
            $query['hash'] = $trackingHash;
        }

        $rebuiltUrl = '';

        if (! empty($parsedUrl['scheme'])) {
            $rebuiltUrl .= $parsedUrl['scheme'] . '://';
        }

        if (! empty($parsedUrl['host'])) {
            $rebuiltUrl .= $parsedUrl['host'];
        }

        if (! empty($parsedUrl['port'])) {
            $rebuiltUrl .= ':' . $parsedUrl['port'];
        }

        $rebuiltUrl .= $parsedUrl['path'] ?? '';

        $queryString = http_build_query($query);

        if ($queryString !== '') {
            $rebuiltUrl .= '?' . $queryString;
        }

        if (! empty($parsedUrl['fragment'])) {
            $rebuiltUrl .= '#' . $parsedUrl['fragment'];
        }

        return $rebuiltUrl;
    }

    /**
     * Obtém a URL base absoluta para gerar links de tracking.
     *
     * @return string URL base com barra final.
     */
    protected function getBaseUrl(): string
    {
        $trackingBase = rtrim((string) getenv('app.trackingBaseURL'), '/');

        if ($trackingBase !== '') {
            return $trackingBase . '/';
        }

        $baseUrl = rtrim((string) (config('App')->baseURL ?? ''), '/');

        if ($baseUrl === '') {
            $baseUrl = rtrim((string) getenv('app.baseURL'), '/');
        }

        if ($baseUrl !== '') {
            return $baseUrl . '/';
        }

        $request = service('request');
        $host = $request?->getServer('HTTP_HOST');
        $scheme = ($request && $request->isSecure()) ? 'https' : 'http';

        if (!empty($host)) {
            return $scheme . '://' . $host . '/';
        }

        return '/';
    }

    /**
     * Gera hash único para tracking
     * 
     * @param int $messageId ID da mensagem
     * @param int $contactId ID do contato
     * @param int $resendNumber Número do reenvio
     * 
     * @return string Hash único
     */
    protected function generateTrackingHash(int $messageId, int $contactId, int $resendNumber): string
    {
        $data = $messageId . '-' . $contactId . '-' . $resendNumber . '-' . time() . '-' . rand(1000, 9999);
        return hash('sha256', $data);
    }

    /**
     * Gera filas para os reenvios que chegaram na data agendada.
     *
     * @param string $now Data/hora atual na zona configurada
     *
     * @return void
     */
    protected function queueResendsDue(string $now): void
    {
        $db = \Config\Database::connect();

        // Buscar regras pendentes agrupadas por mensagem
        // Ordenar por data DESC para processar o mais recente primeiro
        $rules = $db->table('resend_rules')
            ->where('status', 'pending')
            ->where('scheduled_at <=', $now)
            ->orderBy('message_id', 'ASC')
            ->orderBy('scheduled_at', 'DESC')  // Mais recente primeiro
            ->orderBy('resend_number', 'DESC')  // Maior número primeiro
            ->get()
            ->getResultArray();

        if (empty($rules)) {
            return;
        }

        // Agrupar regras por mensagem e processar apenas a mais antiga de cada
        $processedMessages = [];
        
        foreach ($rules as $rule) {
            $messageId = (int) $rule['message_id'];
            
            // Se já processamos um reenvio desta mensagem neste ciclo, pular
            if (in_array($messageId, $processedMessages)) {
                continue;
            }
            
            // Verificar se o envio original já foi completado
            $originalSent = $this->sendModel
                ->where('message_id', $messageId)
                ->where('resend_number', 0)
                ->where('status', 'sent')
                ->countAllResults();
            
            // Se nenhum envio original foi enviado ainda, pular este reenvio
            if ($originalSent === 0) {
                continue;
            }
            
            // Como ordenamos DESC, este é o reenvio mais recente pendente
            // Não precisa verificar anteriores
            
            $contacts = $this->getMessageContacts($messageId);

            if (empty($contacts)) {
                // Se não há contatos para reenviar (todos abriram), marcar como completo
                $db->table('resend_rules')
                    ->where('id', $rule['id'])
                    ->update(['status' => 'completed']);
                continue;
            }

            // Verificar se já existem envios para este resend_number
            $existing = $this->sendModel
                ->where('message_id', $messageId)
                ->where('resend_number', $rule['resend_number'])
                ->countAllResults();

            if ($existing > 0) {
                // Já existem envios para este reenvio, marcar regra como completa
                $db->table('resend_rules')
                    ->where('id', $rule['id'])
                    ->update(['status' => 'completed']);
                continue;
            }

            // Processar este reenvio
            $this->queueMessage($messageId, $contacts, (int) $rule['resend_number']);

            $db->table('resend_rules')
                ->where('id', $rule['id'])
                ->update(['status' => 'completed']);
            
            // Marcar mensagem como processada neste ciclo
            $processedMessages[] = $messageId;
        }
    }

    /**
     * Recupera contatos que NÃO abriram a mensagem em NENHUM dos envios anteriores.
     * Usado para determinar quem deve receber reenvios.
     *
     * @param int $messageId ID da mensagem
     *
     * @return array IDs dos contatos que não abriram
     */
    protected function getMessageContacts(int $messageId): array
    {
        // Buscar contatos que receberam o envio original com sucesso
        $originalContacts = $this->sendModel
            ->distinct()
            ->select('contact_id')
            ->where('message_id', $messageId)
            ->where('resend_number', 0)
            ->where('status', 'sent')
            ->get()
            ->getResultArray();
        
        $originalContactIds = array_column($originalContacts, 'contact_id');
        
        if (empty($originalContactIds)) {
            return [];
        }
        
        // Buscar contatos que abriram EM QUALQUER envio (original ou reenvios)
        $openedContacts = $this->sendModel
            ->distinct()
            ->select('contact_id')
            ->where('message_id', $messageId)
            ->where('opened', 1)
            ->whereIn('contact_id', $originalContactIds)
            ->get()
            ->getResultArray();
        
        $openedContactIds = array_column($openedContacts, 'contact_id');
        
        // Retornar apenas contatos que NÃO abriram em nenhum envio
        return array_diff($originalContactIds, $openedContactIds);
    }

    /**
     * Obtém estatísticas da fila
     * 
     * @return array Estatísticas
     */
    public function getQueueStats(): array
    {
        $pending = $this->sendModel->where('status', 'pending')->countAllResults(false);
        $sent = $this->sendModel->where('status', 'sent')->countAllResults(false);
        $failed = $this->sendModel->where('status', 'failed')->countAllResults(false);

        return [
            'pending' => $pending,
            'sent' => $sent,
            'failed' => $failed,
            'total' => $pending + $sent + $failed,
        ];
    }

    /**
     * Limpa envios antigos
     * 
     * @param int $days Dias para manter
     * 
     * @return int Número de registros removidos
     */
    public function cleanOldSends(int $days = 90): int
    {
        $date = Time::now($this->timezone)
            ->subDays($days)
            ->toDateTimeString();

        return $this->sendModel
            ->where('sent_at <', $date)
            ->where('status', 'sent')
            ->delete();
    }

    /**
     * Recupera a data/hora atual no fuso configurado.
     *
     * @return string
     */
    protected function now(): string
    {
        try {
            $result = $this->sendModel->db()
                ->query('SELECT NOW() AS current_time')
                ->getRow();

            if (!empty($result->current_time)) {
                return Time::parse($result->current_time, $this->timezone)->toDateTimeString();
            }
        } catch (\Throwable $exception) {
            log_message('error', 'Falha ao obter horário do banco: ' . $exception->getMessage());
        }

        return Time::now($this->timezone)->toDateTimeString();
    }

    /**
     * Atualiza o status das mensagens quando não restam envios ou reenvios pendentes.
     *
     * @param array<int> $messageIds IDs das mensagens processadas
     *
     * @return void
     */
    protected function finalizeMessageStatuses(array $messageIds): void
    {
        if (empty($messageIds)) {
            return;
        }

        $uniqueIds = array_unique(array_map('intval', $messageIds));
        $db = \Config\Database::connect();
        $now = $this->now();

        foreach ($uniqueIds as $messageId) {
            $pendingSends = $this->sendModel->builder()
                ->where('message_id', $messageId)
                ->whereIn('status', ['pending', 'sending'])
                ->countAllResults();

            if ($pendingSends > 0) {
                continue;
            }

            $pendingRules = $db->table('resend_rules')
                ->where('message_id', $messageId)
                ->where('status', 'pending')
                ->countAllResults();

            if ($pendingRules > 0) {
                continue;
            }

            $this->messageModel->update($messageId, [
                'status' => 'completed',
                'sent_at' => $now,
            ]);
        }
    }

    /**
     * Recupera mensagens sem envios ou reenvios pendentes.
     *
     * @return array<int> IDs de mensagens finalizáveis
     */
    protected function collectFinishedMessages(): array
    {
        $db = \Config\Database::connect();

        $builder = $db->table('messages')
            ->select('messages.id')
            ->join('message_sends', 'message_sends.message_id = messages.id', 'left')
            ->join('resend_rules', 'resend_rules.message_id = messages.id', 'left')
            ->whereIn('messages.status', ['sending', 'scheduled'])
            ->groupBy('messages.id')
            ->having('SUM(CASE WHEN message_sends.status IN("pending","sending") THEN 1 ELSE 0 END) =', 0, false)
            ->having('SUM(CASE WHEN resend_rules.status = "pending" THEN 1 ELSE 0 END) =', 0, false);

        $rows = $builder->get()->getResultArray();

        return array_map(static fn(array $row) => (int) $row['id'], $rows);
    }
}
