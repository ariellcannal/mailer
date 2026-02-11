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
        
        // CORREÇÃO: Extração segura do messageId das tags
        $messageId = (int) ($mail['tags']['message_id'][0] ?? 0);
        $timestamp = date('Y-m-d H:i:s', strtotime($delivery['timestamp'] ?? 'now'));
        
        foreach ($delivery['recipients'] as $email) {
            $this->updateMessageStatus($email, $messageId, [
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
        $messageId = (int) ($mail['tags']['message_id'][0] ?? 0);
        $bounceType = strtolower((string) ($bounce['bounceType'] ?? 'hard'));
        $reason = (string) ($bounce['bouncedRecipients'][0]['diagnosticCode'] ?? 'N/A');
        
        foreach ($bounce['bouncedRecipients'] as $recipient) {
            $email = strtolower(trim((string) ($recipient['emailAddress'] ?? '')));
            
            $this->updateMessageStatus($email, $messageId, [
                'status'        => 'bounced',
                'bounce_type'   => $bounceType,
                'bounce_reason' => $reason,
                'bounced_at'    => date('Y-m-d H:i:s')
            ]);
            
            // Atualiza o contato para não enviar mais
            $contactModel = new ContactModel();
            $contact = $contactModel->where('email', $email)->first();
            if ($contact) {
                $contactModel->update($contact['id'], [
                    'bounced' => 1,
                    'bounce_type' => $bounceType,
                    'is_active' => 0
                ]);
            }
        }
        
        return ['handled' => true, 'bounced' => count($bounce['bouncedRecipients']), 'complained' => 0];
    }
    
    protected function registerComplaint(array $data): array
    {
        $mail = $data['mail'] ?? [];
        $messageId = (int) ($mail['tags']['message_id'][0] ?? 0);
        $recipients = $data['complaint']['complainedRecipients'] ?? [];
        
        foreach ($recipients as $recipient) {
            $email = strtolower(trim((string) ($recipient['emailAddress'] ?? '')));
            $this->updateMessageStatus($email, $messageId, [
                'status'        => 'complained',
                'complained_at' => date('Y-m-d H:i:s')
            ]);
            
            $contactModel = new ContactModel();
            $contact = $contactModel->where('email', $email)->first();
            if ($contact) {
                $contactModel->update($contact['id'], ['opted_out' => 1, 'is_active' => 0]);
            }
        }
        
        return ['handled' => true, 'bounced' => 0, 'complained' => count($recipients)];
    }
    
    protected function updateMessageStatus(string $email, int $messageId, array $updateData): void
    {
        // Se message_id for 0, o registro nunca será encontrado
        if ($messageId <= 0) {
            log_message('error', "[BounceProcessor] FALHA: message_id ausente no JSON da AWS para {$email}");
            return;
        }
        
        $contactModel = new ContactModel();
        $sendModel = new MessageSendModel();
        
        $contact = $contactModel->where('email', $email)->first();
        if (!$contact) {
            log_message('error', "[BounceProcessor] Contato não encontrado no banco: {$email}");
            return;
        }
        
        // Busca o registro exato para atualizar
        $send = $sendModel->where('message_id', $messageId)
        ->where('contact_id', $contact['id'])
        ->orderBy('id', 'DESC')
        ->first();
        
        if ($send) {
            // Garante que o ID do envio seja passado corretamente para o update
            $sendModel->update($send['id'], $updateData);
            log_message('info', "[BounceProcessor] SUCESSO: Tabela message_sends (ID {$send['id']}) atualizada para {$email}");
        } else {
            log_message('error', "[BounceProcessor] Vínculo não encontrado: MsgID {$messageId} + ContactID {$contact['id']}");
        }
    }
}