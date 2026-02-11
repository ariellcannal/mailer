<?php
declare(strict_types = 1);
namespace App\Libraries\Email;

use App\Libraries\AWS\BounceNotificationService;
use App\Models\ContactModel;
use App\Models\MessageSendModel;
use App\Models\SenderModel;
use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use CodeIgniter\CLI\CLI;

class BounceProcessor
{
    protected BounceNotificationService $notificationService;
    protected SqsClient $sqsClient;
    
    public function __construct()
    {
        $this->validateAwsConfiguration();
        $this->notificationService = new BounceNotificationService();
        $this->sqsClient = $this->notificationService->getSqsClient();
    }
    
    protected function validateAwsConfiguration(): void
    {
        $accessKey = getenv('aws.ses.accessKey');
        $secretKey = getenv('aws.ses.secretKey');
        if (empty($accessKey) || empty($secretKey)) {
            throw new \Exception('Configurações AWS SES faltando no .env');
        }
    }
    
    public function process(int $limit = 50): array
    {
        $senderModel = new SenderModel();
        $domains = array_unique(array_filter(array_column($senderModel->findAll(), 'domain')));
        $summary = ['processed' => 0, 'bounced' => 0, 'complained' => 0, 'errors' => []];
        
        foreach ($domains as $domain) {
            $queueName = $this->notificationService->getBounceQueueName((string) $domain);
            $queueUrl = $this->resolveQueueUrl($queueName);
            if ($queueUrl === null) continue;
            
            $summary = $this->consumeQueue($queueUrl, $limit, $summary);
        }
        return $summary;
    }
    
    protected function resolveQueueUrl(string $queueName): ?string
    {
        try {
            $result = $this->sqsClient->getQueueUrl(['QueueName' => $queueName]);
            return (string) ($result['QueueUrl'] ?? null);
        } catch (AwsException $e) {
            return null;
        }
    }
    
    protected function consumeQueue(string $queueUrl, int $limit, array $summary): array
    {
        $processed = 0;
        while ($processed < $limit) {
            $result = $this->sqsClient->receiveMessage([
                'QueueUrl' => $queueUrl,
                'MaxNumberOfMessages' => 10,
                'WaitTimeSeconds' => 10
            ]);
            
            $messages = $result['Messages'] ?? [];
            if (empty($messages)) break;
            
            foreach ($messages as $message) {
                $handled = $this->handleMessage($message['Body'] ?? '');
                if ($handled['handled']) {
                    $summary['processed']++;
                    $summary['bounced'] += $handled['bounced'];
                    $summary['complained'] += $handled['complained'];
                }
                
                if (!empty($message['ReceiptHandle'])) {
                    $this->sqsClient->deleteMessage([
                        'QueueUrl' => $queueUrl,
                        'ReceiptHandle' => $message['ReceiptHandle']
                    ]);
                }
                $processed++;
            }
        }
        return $summary;
    }
    
    protected function handleMessage(string $payload): array
    {
        log_message('info', "[BounceProcessor]". $payload);
        $data = json_decode($payload, true);
        if (isset($data['Message'])) {
            $data = json_decode($data['Message'], true);
        }
        
        $type = strtolower((string) ($data['notificationType'] ?? ''));
        
        if ($type === 'delivery') {
            return $this->registerDelivery($data);
        }
        if ($type === 'bounce') {
            return $this->registerBounce($data);
        }
        if ($type === 'complaint') {
            return $this->registerComplaint($data);
        }
        
        return ['handled' => false, 'bounced' => 0, 'complained' => 0];
    }
    
    protected function registerDelivery(array $data): array
    {
        $mail = $data['mail'] ?? [];
        $delivery = $data['delivery'] ?? [];
        
        // Usar aws_message_id (messageId da AWS) como vínculo
        $awsMessageId = (string) ($mail['messageId'] ?? '');
        $timestamp = date('Y-m-d H:i:s', strtotime($delivery['timestamp'] ?? 'now'));
        
        if (empty($awsMessageId)) {
            log_message('error', '[BounceProcessor] aws_message_id ausente no webhook de Delivery');
            return ['handled' => false, 'bounced' => 0, 'complained' => 0];
        }
        
        foreach ($delivery['recipients'] as $email) {
            $this->updateMessageStatusByAwsId($email, $awsMessageId, [
                'delivery_at' => $timestamp,
                'status'      => 'sent'
            ]);
        }
        
        return ['handled' => true, 'bounced' => 0, 'complained' => 0];
    }
    
    protected function registerBounce(array $data): array
    {
        $mail = $data['mail'] ?? [];
        $bounce = $data['bounce'] ?? [];
        $awsMessageId = (string) ($mail['messageId'] ?? '');
        $bounceType = strtolower((string) ($bounce['bounceType'] ?? 'hard'));
        $bounceSubtype = (string) ($bounce['bounceSubType'] ?? '');
        $bouncedAt = date('Y-m-d H:i:s', strtotime($bounce['timestamp'] ?? 'now'));
        
        if (empty($awsMessageId)) {
            log_message('error', '[BounceProcessor] aws_message_id ausente no webhook de Bounce');
            return ['handled' => false, 'bounced' => 0, 'complained' => 0];
        }
        
        foreach ($bounce['bouncedRecipients'] as $recipient) {
            $email = strtolower(trim((string) ($recipient['emailAddress'] ?? '')));
            $reason = (string) ($recipient['diagnosticCode'] ?? 'N/A');
            
            // Buscar send pelo aws_message_id
            $sendModel = new MessageSendModel();
            $send = $sendModel->where('aws_message_id', $awsMessageId)->first();
            
            if (!$send) {
                log_message('error', "[BounceProcessor] Send não encontrado para aws_message_id: {$awsMessageId}");
                continue;
            }
            
            // Atualizar message_sends
            $this->updateMessageStatusByAwsId($email, $awsMessageId, [
                'status'        => 'bounced',
                'bounce_type'   => $bounceType,
                'bounce_reason' => $reason,
                'bounced_at'    => $bouncedAt
            ]);
            
            // Atualizar contato
            $contactModel = new ContactModel();
            $contact = $contactModel->where('email', $email)->first();
            if ($contact) {
                $contactModel->update($contact['id'], [
                    'bounced' => 1,
                    'bounce_type' => $bounceType,
                    'is_active' => 0
                ]);
                
                // Registrar na tabela bounces
                $db = \Config\Database::connect();
                $db->table('bounces')->insert([
                    'message_id' => $send['message_id'],
                    'contact_id' => $contact['id'],
                    'message_send_id' => $send['id'],
                    'bounce_type' => $bounceType,
                    'bounce_subtype' => $bounceSubtype,
                    'reason' => $reason,
                    'raw_payload' => json_encode($data),
                    'bounced_at' => $bouncedAt
                ]);
            }
        }
        
        return ['handled' => true, 'bounced' => count($bounce['bouncedRecipients']), 'complained' => 0];
    }
    
    protected function registerComplaint(array $data): array
    {
        $mail = $data['mail'] ?? [];
        $complaint = $data['complaint'] ?? [];
        $awsMessageId = (string) ($mail['messageId'] ?? '');
        $complainedAt = date('Y-m-d H:i:s', strtotime($complaint['timestamp'] ?? 'now'));
        $recipients = $complaint['complainedRecipients'] ?? [];
        
        if (empty($awsMessageId)) {
            log_message('error', '[BounceProcessor] aws_message_id ausente no webhook de Complaint');
            return ['handled' => false, 'bounced' => 0, 'complained' => 0];
        }
        
        foreach ($recipients as $recipient) {
            $email = strtolower(trim((string) ($recipient['emailAddress'] ?? '')));
            
            // Atualizar message_sends
            $this->updateMessageStatusByAwsId($email, $awsMessageId, [
                'status'        => 'complained',
                'complained_at' => $complainedAt
            ]);
            
            // Atualizar contato
            $contactModel = new ContactModel();
            $contact = $contactModel->where('email', $email)->first();
            if ($contact) {
                $contactModel->update($contact['id'], ['opted_out' => 1, 'is_active' => 0]);
            }
        }
        
        return ['handled' => true, 'bounced' => 0, 'complained' => count($recipients)];
    }
    
    /**
     * Atualiza status de message_sends usando aws_message_id como vínculo
     */
    protected function updateMessageStatusByAwsId(string $email, string $awsMessageId, array $updateData): void
    {
        if (empty($awsMessageId)) {
            log_message('error', "[BounceProcessor] FALHA: aws_message_id vazio para {$email}");
            return;
        }
        
        $contactModel = new ContactModel();
        $sendModel = new MessageSendModel();
        
        $contact = $contactModel->where('email', $email)->first();
        if (!$contact) {
            log_message('error', "[BounceProcessor] Contato não encontrado no banco: {$email}");
            return;
        }
        
        // Busca o registro pelo aws_message_id
        $send = $sendModel->where('aws_message_id', $awsMessageId)
            ->where('contact_id', $contact['id'])
            ->first();
        
        if ($send) {
            $sendModel->update($send['id'], $updateData);
            log_message('info', "[BounceProcessor] SUCESSO: message_sends (ID {$send['id']}) atualizado para {$email} (AWS ID: {$awsMessageId})");
        } else {
            log_message('error', "[BounceProcessor] Vínculo não encontrado: AWS MessageId {$awsMessageId} + ContactID {$contact['id']}");
        }
    }
}