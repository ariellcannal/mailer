<?php
namespace App\Libraries\Email;

use App\Libraries\AWS\BounceNotificationService;
use App\Libraries\AWS\SESService;
use App\Models\MessageSendModel;
use App\Models\ContactModel;
use App\Models\MessageModel;
use App\Models\SenderModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\BaseConnection;

/**
 * Queue Manager Otimizado
 * * Gerencia fila de envio com foco em eficiência de memória e CPU.
 */
class QueueManager
{

    protected $sesService;

    protected BounceNotificationService $bounceService;

    protected $sendModel;

    protected $contactModel;

    protected $messageModel;

    protected string $timezone = 'America/Sao_Paulo';

    protected int $throttleRate = 14;

    /**
     * Cache para evitar consultas repetidas ao mesmo remetente no mesmo lote
     */
    protected array $senderCache = [];

    public function __construct()
    {
        $this->sendModel = new MessageSendModel();
        $this->contactModel = new ContactModel();
        $this->messageModel = new MessageModel();
        $this->timezone = config('App')->appTimezone ?? $this->timezone;
        $this->throttleRate = (int) getenv('app.throttleRate') ?: 14;
    }

    protected function getSESService(): SESService
    {
        if ($this->sesService === null)
            $this->sesService = new SESService();
        return $this->sesService;
    }

    protected function getBounceService(): BounceNotificationService
    {
        if ($this->bounceService === null)
            $this->bounceService = new BounceNotificationService();
        return $this->bounceService;
    }

    /**
     * Processa fila de envio utilizando Geradores para poupar memória
     */
    public function processQueue(int $batchSize = 100): array
    {
        $now = $this->now();
        $this->queueResendsDue($now);

        // Otimização Massiva: Impede que o CI4 guarde todas as queries na RAM
        $db = \Config\Database::connect();
        if (property_exists($db, 'saveQueries')) {
            $db->saveQueries = false;
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $processedMessages = [];

        // Itera sobre a fila sem carregar todos os objetos de uma vez
        foreach ($this->pendingSendsGenerator($batchSize, $now) as $send) {
            try {
                if (($send['message_status'] ?? '') === 'scheduled') {
                    $this->messageModel->update($send['message_id'], [
                        'status' => 'sending'
                    ]);
                }

                $processedMessages[] = (int) $send['message_id'];

                $result = $this->sendEmail($send);

                if ($result['success']) {
                    $sent ++;
                    // Respeita a taxa máxima do SES (Throttling)
                    usleep(1000000 / $this->throttleRate);
                } elseif (($result['status'] ?? '') === 'skipped') {
                    $skipped ++;
                } else {
                    $failed ++;
                }

                // Coleta de lixo periódica para manter a RAM estável
                if (($sent + $failed + $skipped) % 50 === 0) {
                    gc_collect_cycles();
                }
            } catch (\Exception $e) {
                $failed ++;
                log_message('error', 'Queue processing error: ' . $e->getMessage());
            }
        }

        $this->finalizeMessageStatuses($processedMessages);
        $this->senderCache = []; // Limpa cache de remetentes após o lote

        return [
            'success' => true,
            'processed' => $sent + $failed + $skipped,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped
        ];
    }

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
            // Verificar se o envio original já foi completado
            $originalSent = $this->sendModel->where('message_id', $rule['message_id'])
                ->where('resend_number', 0)
                ->where('status', 'sent')
                ->countAllResults();

            // Se nenhum envio original foi enviado ainda, pular este reenvio
            if ($originalSent === 0) {
                continue;
            }

            $contacts = $this->getMessageContacts((int) $rule['message_id']);

            if (empty($contacts)) {
                // Se não há contatos para reenviar (todos abriram), marcar como completo
                $db->table('resend_rules')
                    ->where('id', $rule['id'])
                    ->update([
                    'status' => 'completed'
                ]);
                continue;
            }

            $existing = $this->sendModel->where('message_id', $rule['message_id'])
                ->where('resend_number', $rule['resend_number'])
                ->countAllResults();

            if ($existing > 0) {
                continue;
            }

            $this->queueMessage((int) $rule['message_id'], $contacts, (int) $rule['resend_number']);

            $db->table('resend_rules')
                ->where('id', $rule['id'])
                ->update([
                'status' => 'completed'
            ]);
        }
    }

    /**
     * Recupera contatos que NÃO abriram a mensagem original.
     * Usado para determinar quem deve receber reenvios.
     *
     * @param int $messageId
     *            ID da mensagem
     *            
     * @return array IDs dos contatos que não abriram
     */
    protected function getMessageContacts(int $messageId): array
    {
        $rows = $this->sendModel->distinct()
            ->select('contact_id')
            ->where('message_id', $messageId)
            ->where('resend_number', 0)
            ->where('opened', 0)
            -> // Apenas contatos que NÃO abriram
        where('status', 'sent')
            -> // Apenas envios bem-sucedidos
        get()
            ->getResultArray();

        return array_column($rows, 'contact_id');
    }

    /**
     * Busca envios pendentes usando Cursor (Unbuffered Row) para memória
     */
    protected function pendingSendsGenerator(int $batchSize, string $now): \Generator
    {
        $builder = $this->sendModel->builder()
            ->select('message_sends.*, messages.status AS message_status, resend_rules.subject_override')
            ->join('messages', 'messages.id = message_sends.message_id')
            ->join('resend_rules', 'resend_rules.message_id = message_sends.message_id AND resend_rules.resend_number = message_sends.resend_number', 'left')
            ->where('message_sends.status', 'pending')
            ->groupStart()
            ->where('message_sends.resend_number', 0)
            ->orGroupStart()
            ->where('message_sends.resend_number >', 0)
            ->groupStart()
            ->where('resend_rules.status', 'pending')
            ->where('resend_rules.scheduled_at <=', $now)
            ->orWhere('resend_rules.status', 'completed')
            ->groupEnd()
            ->groupEnd()
            ->groupEnd()
            ->groupStart()
            ->where('messages.scheduled_at <=', $now)
            ->orWhere('messages.scheduled_at', null)
            ->groupEnd()
            ->orderBy('message_sends.id', 'ASC')
            ->limit($batchSize);
        // echo $builder->getCompiledSelect();
        $query = $builder->get();
        while ($row = $query->getUnbufferedRow('array')) {
            yield $row;
        }
    }

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
                ->whereIn('status', [
                'pending',
                'sending'
            ])
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
                'sent_at' => $now
            ]);
        }
    }

    protected function sendEmail(array $send): array
    {
        $message = $this->messageModel->find($send['message_id']);
        if (! $message)
            return [
                'success' => false,
                'error' => 'Message not found'
            ];

        // Otimização: Somente busca as colunas necessárias para o envio
        $contact = $this->contactModel->select('id, email, name, nickname, is_active, opted_out, bounced, bounce_type')->find($send['contact_id']);
        if (! $contact)
            return [
                'success' => false,
                'error' => 'Contact not found'
            ];

        // Remover apenas: inativos, opted_out, ou hard bounces
        // Soft bounces são permitidos (podem ser temporários)
        $isHardBounce = $contact['bounced'] && strtolower($contact['bounce_type'] ?? '') === 'hard';
        
        if (! $contact['is_active'] || $contact['opted_out'] || $isHardBounce) {
            $this->sendModel->update($send['id'], [
                'status' => 'cancelled'
            ]);
            return [
                'success' => false,
                'status' => 'skipped',
                'error' => 'Contact inactive'
            ];
        }

        // Cache de Remetente: evita repetir a consulta 100x por lote
        if (! isset($this->senderCache[$message['sender_id']])) {
            $senderModel = new SenderModel();
            $this->senderCache[$message['sender_id']] = $senderModel->find($message['sender_id']);
        }
        $sender = $this->senderCache[$message['sender_id']];

        if (! $sender)
            return [
                'success' => false,
                'error' => 'Sender not found'
            ];

        $htmlBody = $this->prepareEmailContent($message['html_content'], $contact, $send['tracking_hash']);
        $emailSubject = $send['subject_override'] ?: $message['subject'];

        $result = $this->getSESService()->sendEmail(from: $sender['email'], fromName: $message['from_name'], to: $contact['email'], subject: $emailSubject, htmlBody: $htmlBody, replyTo: $message['reply_to'], tags: [
            [
                'Name' => 'message_id',
                'Value' => (string) $send['message_id']
            ]
        ]);

        if ($result['success']) {
            $updateData = [
                'status' => 'sent',
                'sent_at' => $this->now()
            ];
            
            // Salvar aws_message_id para vincular com webhooks de bounce/complaint/delivery
            if (!empty($result['messageId'])) {
                $updateData['aws_message_id'] = $result['messageId'];
            }
            
            $this->sendModel->update($send['id'], $updateData);
            $this->messageModel->increment($message['id'], 'total_sent');
            return [
                'success' => true
            ];
        }

        $this->sendModel->update($send['id'], [
            'status' => 'failed'
        ]);
        return [
            'success' => false,
            'error' => $result['error']
        ];
    }

    /**
     * Otimização: Verificação de existência usando select('1') em vez de countAll
     */
    protected function processSingleResend(array $rule, $db): void
    {
        $existing = $this->sendModel->builder()
            ->select('1')
            ->where('message_id', $rule['message_id'])
            ->where('resend_number', $rule['resend_number'])
            ->limit(1)
            ->get()
            ->getRow();

        if ($existing) {
            $db->table('resend_rules')
                ->where('id', $rule['id'])
                ->update([
                'status' => 'completed'
            ]);
            return;
        }

        $contacts = $this->getMessageContacts((int) $rule['message_id']);
        if (empty($contacts)) {
            $db->table('resend_rules')
                ->where('id', $rule['id'])
                ->update([
                'status' => 'completed'
            ]);
            return;
        }

        $this->queueMessage((int) $rule['message_id'], $contacts, (int) $rule['resend_number']);
        $db->table('resend_rules')
            ->where('id', $rule['id'])
            ->update([
            'status' => 'completed'
        ]);
    }

    // ... (Manter métodos de Tracking, Nickname e Now inalterados conforme lógica anterior)
    protected function now(): string
    {
        return Time::now($this->timezone)->toDateTimeString();
    }

    /**
     * Adiciona mensagens à fila de envio
     *
     * @param int $messageId
     *            ID da mensagem
     * @param array $contactIds
     *            IDs dos contatos
     * @param int $resendNumber
     *            Número do reenvio (0 = envio original)
     * @return array Resultado da operação
     */
    public function queueMessage(int $messageId, array $contactIds, int $resendNumber = 0): array
    {
        // Verificar duplicação: buscar envios existentes
        $existing = $this->sendModel->select('contact_id, resend_number')
            ->where('message_id', $messageId)
            ->findAll();

        // Criar mapa de envios existentes
        $existingMap = [];
        foreach ($existing as $send) {
            $key = $send['contact_id'] . '_' . $send['resend_number'];
            $existingMap[$key] = true;
        }

        // Filtrar contatos que já possuem envio
        $newContactIds = [];
        $skipped = 0;

        foreach ($contactIds as $contactId) {
            $key = $contactId . '_' . $resendNumber;
            if (! isset($existingMap[$key])) {
                $newContactIds[] = $contactId;
            } else {
                $skipped ++;
            }
        }

        // Verificar limite de 4 envios por contato (1 original + 3 reenvios)
        if ($resendNumber > 3) {
            log_message('warning', "Tentativa de criar reenvio #{$resendNumber} para mensagem {$messageId} (máximo: 3)");
            return [
                'success' => false,
                'error' => 'Máximo de 3 reenvios permitidos',
                'queued' => 0,
                'skipped' => count($contactIds)
            ];
        }

        if (empty($newContactIds)) {
            log_message('info', "Nenhum novo envio para adicionar (todos já existem). Skipped: {$skipped}");
            return [
                'success' => true,
                'queued' => 0,
                'skipped' => $skipped
            ];
        }

        // Inserir em lote para performance
        $batch = [];
        $queued = 0;

        foreach ($newContactIds as $contactId) {
            $batch[] = [
                'message_id' => $messageId,
                'contact_id' => $contactId,
                'resend_number' => $resendNumber,
                'tracking_hash' => hash('sha256', $messageId . $contactId . $resendNumber . time() . rand()),
                'status' => 'pending'
            ];

            // Inserção em lote para performance
            if (count($batch) >= 100) {
                $this->sendModel->insertBatch($batch);
                $queued += count($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $this->sendModel->insertBatch($batch);
            $queued += count($batch);
        }

        log_message('info', "Fila atualizada: {$queued} novos envios, {$skipped} duplicados ignorados");

        return [
            'success' => true,
            'queued' => $queued,
            'skipped' => $skipped
        ];
    }

    protected function prepareEmailContent(string $htmlContent, array $contact, string $trackingHash): string
    {
        $htmlContent = str_replace([
            '{{nome}}',
            '{{apelido}}',
            '{{email}}'
        ], [
            $contact['name'],
            $contact['nickname'],
            $contact['email']
        ], $htmlContent);
        $baseUrl = $this->getBaseUrl();
        $pixel = '<img src="' . $baseUrl . 'track/open/' . $trackingHash . '" width="1" height="1" style="display:none;" />';
        $htmlContent = stripos($htmlContent, '</body>') !== false ? str_ireplace('</body>', $pixel . '</body>', $htmlContent) : $htmlContent . $pixel;
        return $htmlContent;
    }

    protected function getBaseUrl(): string
    {
        return rtrim(config('App')->baseURL ?? getenv('app.baseURL'), '/') . '/';
    }
}