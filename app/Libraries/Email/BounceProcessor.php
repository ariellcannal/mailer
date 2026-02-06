<?php

declare(strict_types=1);

namespace App\Libraries\Email;

use App\Libraries\AWS\BounceNotificationService;
use App\Models\CampaignModel;
use App\Models\ContactModel;
use App\Models\MessageModel;
use App\Models\MessageSendModel;
use App\Models\SenderModel;
use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;

/**
 * Processador responsável por identificar e registrar bounces e complaints.
 */
class BounceProcessor
{
    protected BounceNotificationService $notificationService;

    protected SqsClient $sqsClient;

    public function __construct()
    {
        // Verificar configurações AWS antes de inicializar
        $this->validateAwsConfiguration();
        
        $this->notificationService = new BounceNotificationService();
        $this->sqsClient = $this->notificationService->getSqsClient();
    }
    
    /**
     * Valida se as configurações AWS estão corretas.
     * 
     * @throws \Exception Se configurações estiverem faltando
     */
    protected function validateAwsConfiguration(): void
    {
        $accessKey = getenv('aws.ses.accessKey');
        $secretKey = getenv('aws.ses.secretKey');
        $region = getenv('aws.ses.region') ?: 'us-east-1';
        
        $errors = [];
        
        if (empty($accessKey)) {
            $errors[] = 'aws.ses.accessKey não configurada';
        }
        
        if (empty($secretKey)) {
            $errors[] = 'aws.ses.secretKey não configurada';
        }
        
        if (!empty($errors)) {
            $errorMsg = 'Configurações AWS SES faltando: ' . implode(', ', $errors);
            log_message('error', $errorMsg);
            throw new \Exception($errorMsg);
        }
        
        log_message('info', "Configurações AWS validadas: Region={$region}, AccessKey=" . substr($accessKey, 0, 8) . '...');
    }

    /**
     * Consome as filas de bounces configuradas para os remetentes ativos.
     *
     * @param int $limit Quantidade máxima de mensagens por fila.
     * @return array<string, mixed>
     */
    public function process(int $limit = 50): array
    {
        log_message('info', '[BounceProcessor] Iniciando processamento de bounces/complaints');
        
        $senderModel = new SenderModel();
        $domains = array_unique(array_filter(array_column($senderModel->findAll(), 'domain')));
        
        log_message('info', '[BounceProcessor] Domínios encontrados: ' . implode(', ', $domains));

        $summary = [
            'processed' => 0,
            'bounced' => 0,
            'complained' => 0,
            'errors' => [],
        ];

        foreach ($domains as $domain) {
            $queueName = $this->notificationService->getBounceQueueName((string) $domain);
            log_message('info', "[BounceProcessor] Processando domínio: {$domain}, fila: {$queueName}");
            
            $queueUrl = $this->resolveQueueUrl($queueName);

            if ($queueUrl === null) {
                $error = 'Fila não encontrada para o domínio ' . $domain;
                $summary['errors'][] = $error;
                log_message('warning', "[BounceProcessor] {$error}");
                continue;
            }
            
            log_message('info', "[BounceProcessor] Fila encontrada: {$queueUrl}");

            $summary = $this->consumeQueue($queueUrl, $limit, $summary);
        }
        
        log_message('info', '[BounceProcessor] Processamento finalizado: ' . json_encode($summary));

        return $summary;
    }

    /**
     * Localiza a URL da fila.
     *
     * @param string $queueName Nome da fila.
     * @return string|null
     */
    protected function resolveQueueUrl(string $queueName): ?string
    {
        try {
            $result = $this->sqsClient->getQueueUrl(['QueueName' => $queueName]);

            return (string) ($result['QueueUrl'] ?? null);
        } catch (AwsException $exception) {
            log_message('error', 'Erro ao resolver fila de bounce: ' . $exception->getMessage());

            return null;
        }
    }

    /**
     * Consome mensagens de uma fila específica.
     *
     * @param string                 $queueUrl URL da fila.
     * @param int                    $limit    Limite de mensagens a processar.
     * @param array<string, mixed>   $summary  Resumo atual.
     * @return array<string, mixed>
     */
    protected function consumeQueue(string $queueUrl, int $limit, array $summary): array
    {
        $processed = 0;

        while ($processed < $limit) {
            $result = $this->sqsClient->receiveMessage([
                'QueueUrl' => $queueUrl,
                'MaxNumberOfMessages' => 10,
                'WaitTimeSeconds' => 1,
            ]);

            $messages = $result['Messages'] ?? [];

            if (empty($messages)) {
                break;
            }

            foreach ($messages as $message) {
                $handled = $this->handleMessage($message['Body'] ?? '');

                if ($handled['handled']) {
                    $summary['processed']++;
                    $summary['bounced'] += $handled['bounced'];
                    $summary['complained'] += $handled['complained'];
                } elseif (!empty($handled['error'])) {
                    $summary['errors'][] = $handled['error'];
                }

                if (!empty($message['ReceiptHandle'])) {
                    $this->sqsClient->deleteMessage([
                        'QueueUrl' => $queueUrl,
                        'ReceiptHandle' => $message['ReceiptHandle'],
                    ]);
                }

                $processed++;

                if ($processed >= $limit) {
                    break 2;
                }
            }
        }

        return $summary;
    }

    /**
     * Processa uma mensagem individual e retorna o impacto nos contadores.
     *
     * @param string $payload Conteúdo bruto da mensagem.
     * @return array<string, mixed>
     */
    protected function handleMessage(string $payload): array
    {
        $data = json_decode($payload, true);

        if (!is_array($data)) {
            return ['handled' => false, 'bounced' => 0, 'complained' => 0, 'error' => 'Payload inválido'];
        }

        // Quando RawMessageDelivery estiver desabilitado, a mensagem vem envolvida pelo SNS
        if (isset($data['Message']) && is_string($data['Message'])) {
            $data = json_decode($data['Message'], true) ?: $data;
        }

        $type = strtolower((string) ($data['notificationType'] ?? ''));

        if ($type === 'bounce') {
            return $this->registerBounce($data);
        }

        if ($type === 'complaint') {
            return $this->registerComplaint($data);
        }

        return ['handled' => false, 'bounced' => 0, 'complained' => 0, 'error' => 'Tipo de notificação desconhecido'];
    }

    /**
     * Registra um bounce na base local.
     *
     * @param array $data Conteúdo do evento de bounce.
     * @return array<string, int|bool>
     */
    protected function registerBounce(array $data): array
    {
        $mail = $data['mail'] ?? [];
        $bounce = $data['bounce'] ?? [];
        $bounceType = strtolower((string) ($bounce['bounceType'] ?? 'hard'));
        $reason = (string) ($bounce['bouncedRecipients'][0]['diagnosticCode'] ?? '');

        $messageId = (int) ($mail['tags']['message_id'][0] ?? 0);
        $recipients = $bounce['bouncedRecipients'] ?? [];
        
        log_message('info', "[BounceProcessor] Registrando bounce: tipo={$bounceType}, messageId={$messageId}, recipients=" . count($recipients));

        foreach ($recipients as $recipient) {
            $email = strtolower(trim((string) ($recipient['emailAddress'] ?? '')));

            if ($email === '') {
                continue;
            }
            
            log_message('info', "[BounceProcessor] Bounce registrado: {$email} (tipo: {$bounceType})");
            $this->applyContactBounce($email, $messageId, $bounceType, $reason);
        }

        return ['handled' => true, 'bounced' => count($recipients), 'complained' => 0];
    }

    /**
     * Registra uma reclamação.
     *
     * @param array $data Conteúdo do evento de complaint.
     * @return array<string, int|bool>
     */
    protected function registerComplaint(array $data): array
    {
        $mail = $data['mail'] ?? [];
        $complaint = $data['complaint'] ?? [];
        $messageId = (int) ($mail['tags']['message_id'][0] ?? 0);
        $recipients = $complaint['complainedRecipients'] ?? [];
        
        log_message('warning', "[BounceProcessor] Registrando complaint (spam): messageId={$messageId}, recipients=" . count($recipients));

        foreach ($recipients as $recipient) {
            $email = strtolower(trim((string) ($recipient['emailAddress'] ?? '')));

            if ($email === '') {
                continue;
            }
            
            log_message('warning', "[BounceProcessor] Complaint registrado: {$email} (marcado como opt-out)");
            $this->applyComplaint($email, $messageId);
        }

        return ['handled' => true, 'bounced' => 0, 'complained' => count($recipients)];
    }

    /**
     * Atualiza registros de contato, envio e campanha para bounces.
     *
     * @param string $email      Email do destinatário.
     * @param int    $messageId  Identificador interno da mensagem.
     * @param string $bounceType Tipo do bounce.
     * @param string $reason     Mensagem/diagnóstico do bounce.
     * @return void
     */
    protected function applyContactBounce(string $email, int $messageId, string $bounceType, string $reason): void
    {
        $contactModel = new ContactModel();
        $sendModel = new MessageSendModel();
        $messageModel = new MessageModel();
        $campaignModel = new CampaignModel();

        $contact = $contactModel->where('email', $email)->first();

        if (!$contact) {
            return;
        }

        $contactModel->markAsBounced((int) $contact['id'], $bounceType);

        if ($messageId > 0) {
            $send = $sendModel
                ->where('message_id', $messageId)
                ->where('contact_id', $contact['id'])
                ->orderBy('id', 'DESC')
                ->first();

            if ($send) {
                $sendModel->update((int) $send['id'], [
                    'status' => 'bounced',
                    'bounce_type' => $bounceType,
                    'bounce_reason' => $reason,
                    'bounced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $message = $messageModel->find($messageId);

            if ($message) {
                $messageModel->increment($messageId, 'total_bounces');

                if (!empty($message['campaign_id'])) {
                    $campaignModel->increment((int) $message['campaign_id'], 'total_bounces');
                }
            }
        }
    }

    /**
     * Aplica marcação de complaint.
     *
     * @param string $email     Email do destinatário.
     * @param int    $messageId Identificador interno da mensagem.
     * @return void
     */
    protected function applyComplaint(string $email, int $messageId): void
    {
        $contactModel = new ContactModel();
        $sendModel = new MessageSendModel();

        $contact = $contactModel->where('email', $email)->first();

        if (!$contact) {
            return;
        }

        $contactModel->optOut((int) $contact['id']);

        if ($messageId > 0) {
            $send = $sendModel
                ->where('message_id', $messageId)
                ->where('contact_id', $contact['id'])
                ->orderBy('id', 'DESC')
                ->first();

            if ($send) {
                $sendModel->update((int) $send['id'], [
                    'status' => 'complained',
                    'complained_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
