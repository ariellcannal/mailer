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
        $this->sesService = new SESService();
        $this->bounceService = new BounceNotificationService();
        $this->sendModel = new MessageSendModel();
        $this->contactModel = new ContactModel();
        $this->messageModel = new MessageModel();

        $this->timezone = config('App')->appTimezone ?? $this->timezone;

        $this->throttleRate = (int) getenv('app.throttleRate') ?: 14;
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
            $flowResult = $this->bounceService->ensureBounceFlow($sender['domain']);

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
        $result = $this->sesService->sendEmail(
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
        // Substitui variáveis do contato
        $htmlContent = str_replace('{{nome}}', $contact['name'] ?? '', $htmlContent);
        $htmlContent = str_replace('{{email}}', $contact['email'], $htmlContent);
        $htmlContent = str_replace('{{name}}', $contact['name'] ?? '', $htmlContent);

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

        // Substitui link de opt-out
        $optoutUrl = $baseUrl . 'optout/' . $trackingHash;
        $htmlContent = str_replace('{{optout_link}}', $optoutUrl, $htmlContent);
        $htmlContent = str_replace('{{unsubscribe_link}}', $optoutUrl, $htmlContent);

        // Substitui link de visualização web
        $webviewUrl = $baseUrl . 'webview/' . $trackingHash;
        $htmlContent = str_replace('{{webview_link}}', $webviewUrl, $htmlContent);
        $htmlContent = str_replace('{{view_online}}', $webviewUrl, $htmlContent);

        return $htmlContent;
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
        
        // Regex para encontrar links
        $pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])((?:(?!\1).)*)\1/i';
        
        $html = preg_replace_callback($pattern, function($matches) use ($trackingHash, $baseUrl) {
            $quote = $matches[1];
            $url = $matches[2];
            
            // Ignora links especiais
            if (strpos($url, 'mailto:') === 0 || 
                strpos($url, 'tel:') === 0 ||
                strpos($url, '#') === 0 ||
                strpos($url, 'javascript:') === 0 ||
                strpos($url, '{{') !== false) {
                return $matches[0];
            }
            
            // Cria URL de tracking
            $trackingUrl = $baseUrl . 'track/click/' . $trackingHash . '?url=' . urlencode($url);
            
            return '<a href=' . $quote . $trackingUrl . $quote;
        }, $html);

        return $html;
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

        $rules = $db->table('resend_rules')
            ->where('status', 'pending')
            ->where('scheduled_at <=', $now)
            ->get()
            ->getResultArray();

        if (empty($rules)) {
            return;
        }

        foreach ($rules as $rule) {
            $contacts = $this->getMessageContacts((int) $rule['message_id']);

            if (empty($contacts)) {
                continue;
            }

            $existing = $this->sendModel
                ->where('message_id', $rule['message_id'])
                ->where('resend_number', $rule['resend_number'])
                ->countAllResults();

            if ($existing > 0) {
                continue;
            }

            $this->queueMessage((int) $rule['message_id'], $contacts, (int) $rule['resend_number']);

            $db->table('resend_rules')
                ->where('id', $rule['id'])
                ->update(['status' => 'completed']);
        }
    }

    /**
     * Recupera contatos já vinculados à mensagem original.
     *
     * @param int $messageId ID da mensagem
     *
     * @return array
     */
    protected function getMessageContacts(int $messageId): array
    {
        $rows = $this->sendModel
            ->select('DISTINCT contact_id')
            ->where('message_id', $messageId)
            ->where('resend_number', 0)
            ->get()
            ->getResultArray();

        return array_column($rows, 'contact_id');
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
                'status' => 'sent',
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
            ->having('SUM(CASE WHEN message_sends.status IN("pending","sending") THEN 1 ELSE 0 END)', 0)
            ->having('SUM(CASE WHEN resend_rules.status = "pending" THEN 1 ELSE 0 END)', 0);

        $rows = $builder->get()->getResultArray();

        return array_map(static fn(array $row) => (int) $row['id'], $rows);
    }
}
