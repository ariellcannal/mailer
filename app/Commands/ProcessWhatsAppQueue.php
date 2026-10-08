<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\WhatsApp\WhatsAppCloudService;
use App\Models\ContactModel;
use App\Models\WhatsAppMessageModel;
use App\Models\WhatsAppNumberModel;
use App\Models\WhatsAppSendModel;
use App\Models\WhatsAppTemplateModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProcessWhatsAppQueue extends BaseCommand
{
    protected $group = 'WhatsApp';
    protected $name = 'whatsapp:process';
    protected $description = 'Expande e processa a fila de envios WhatsApp pela Meta Cloud API.';

    public function run(array $params)
    {
        if (function_exists('proc_nice')) {
            proc_nice(10);
        }

        set_time_limit(180);
        ini_set('memory_limit', '128M');
        gc_enable();

        $db = \Config\Database::connect();
        if (property_exists($db, 'saveQueries')) {
            $db->saveQueries = false;
        }

        $lock = fopen(WRITEPATH . 'whatsapp_process.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            CLI::write('Worker WhatsApp já está em execução.', 'yellow');
            if (is_resource($lock)) {
                fclose($lock);
            }
            return;
        }

        try {
            $this->processDueMessages();
        } catch (\Throwable $exception) {
            log_message('error', 'WhatsApp queue worker: ' . $exception->getMessage());
            CLI::error('Falha ao processar a fila WhatsApp: ' . $exception->getMessage());
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
            gc_collect_cycles();
        }
    }

    private function processDueMessages(): void
    {
        $messageModel = new WhatsAppMessageModel();
        $messages = $messageModel->groupStart()
            ->where('status', 'queued')
            ->orGroupStart()
            ->where('status', 'scheduled')
            ->where('scheduled_at <=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orWhere('status', 'sending')
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->findAll(5);

        foreach ($messages as $message) {
            $this->processMessage($message);
        }
    }

    /** @param array<string, mixed> $message */
    private function processMessage(array $message): void
    {
        $companyId = (int) $message['company_id'];
        $messageModel = (new WhatsAppMessageModel())->forCompany($companyId);
        $sendModel = (new WhatsAppSendModel())->forCompany($companyId);
        $messageModel->update((int) $message['id'], ['status' => 'sending']);

        $this->expandRecipients($message, $companyId);

        $number = (new WhatsAppNumberModel())->forCompany($companyId)->find((int) $message['whatsapp_number_id']);
        $template = (new WhatsAppTemplateModel())->forCompany($companyId)->find((int) $message['template_id']);
        if (!$number || !$template) {
            $messageModel->update((int) $message['id'], ['status' => 'failed']);
            log_message('error', 'WhatsApp message ' . $message['id'] . ' references a missing number or template.');
            return;
        }

        $contactModel = (new ContactModel())->forCompany($companyId);
        $pending = $sendModel->where('whatsapp_message_id', (int) $message['id'])
            ->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->findAll(50);

        try {
            $service = $pending === [] ? null : new WhatsAppCloudService($companyId);
        } catch (\Throwable $exception) {
            foreach ($pending as $send) {
                $sendModel->update((int) $send['id'], [
                    'status' => 'failed',
                    'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                ]);
            }
            $messageModel->set('total_failed', 'total_failed + ' . count($pending), false)->where('id', (int) $message['id'])->update();
            $messageModel->update((int) $message['id'], ['status' => 'failed']);
            log_message('error', 'WhatsApp message ' . $message['id'] . ' could not initialize Meta API: ' . $exception->getMessage());
            return;
        }

        foreach ($pending as $send) {
            $contact = $contactModel->find((int) $send['contact_id']);
            if (!$contact || !(bool) $contact['is_active'] || (bool) $contact['opted_out'] || (bool) $contact['bounced']) {
                $sendModel->update((int) $send['id'], [
                    'status' => 'failed',
                    'error_message' => 'Contato inativo, descadastrado ou bloqueado.',
                ]);
                continue;
            }

            try {
                if ($service === null) {
                    continue;
                }
                $variableMapping = json_decode((string) ($message['variable_mapping'] ?? '{}'), true);
                $result = $service->sendTemplate(
                    $number,
                    $template,
                    $contact,
                    (string) $send['destination_phone'],
                    is_array($variableMapping) ? $variableMapping : []
                );
                $metaMessageId = $result['messages'][0]['id'] ?? null;
                if (!$metaMessageId) {
                    throw new \RuntimeException('A Meta não retornou o ID da mensagem.');
                }

                $sendModel->update((int) $send['id'], [
                    'status' => 'sent',
                    'meta_message_id' => $metaMessageId,
                    'sent_at' => date('Y-m-d H:i:s'),
                ]);
                $messageModel->set('total_sent', 'total_sent + 1', false)->where('id', (int) $message['id'])->update();
                usleep(250000);
            } catch (\Throwable $exception) {
                $sendModel->update((int) $send['id'], [
                    'status' => 'failed',
                    'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                ]);
                $messageModel->set('total_failed', 'total_failed + 1', false)->where('id', (int) $message['id'])->update();
            }
        }

        $hasMoreRecipients = $this->hasUnexpandedRecipients($message, $companyId);
        $pendingCount = $sendModel->where('whatsapp_message_id', (int) $message['id'])
            ->where('status', 'pending')->countAllResults();
        if (!$hasMoreRecipients && $pendingCount === 0) {
            $finalMessage = $messageModel->find((int) $message['id']);
            $finalStatus = (int) ($finalMessage['total_sent'] ?? 0) > 0 ? 'sent' : 'failed';
            $messageModel->update((int) $message['id'], ['status' => $finalStatus]);
        }

        CLI::write('Envio WhatsApp #' . $message['id'] . ' processado.', 'green');
    }

    /** @param array<string, mixed> $message */
    private function expandRecipients(array $message, int $companyId): void
    {
        $listIds = json_decode((string) ($message['list_ids'] ?? '[]'), true);
        if (!is_array($listIds) || $listIds === []) {
            return;
        }

        $db = \Config\Database::connect();
        $rows = $db->table('contacts')
            ->select('contacts.id, contacts.phone')
            ->join('contact_list_members', 'contact_list_members.contact_id = contacts.id')
            ->join('contact_lists', 'contact_lists.id = contact_list_members.list_id')
            ->join('whatsapp_messages', 'whatsapp_messages.id = ' . (int) $message['id'])
            ->join('whatsapp_sends', 'whatsapp_sends.whatsapp_message_id = whatsapp_messages.id AND whatsapp_sends.contact_id = contacts.id', 'left')
            ->where('contacts.company_id', $companyId)
            ->where('contacts.is_active', 1)
            ->where('contacts.opted_out', 0)
            ->where('contacts.bounced', 0)
            ->where('contact_list_members.company_id', $companyId)
            ->where('contact_lists.company_id', $companyId)
            ->whereIn('contact_list_members.list_id', array_map('intval', $listIds))
            ->where('contacts.phone !=', '')
            ->where('whatsapp_sends.id', null)
            ->groupBy('contacts.id, contacts.phone')
            ->orderBy('contacts.id', 'ASC')
            ->limit(1000)
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return;
        }

        $sendModel = new WhatsAppSendModel();
        $batch = [];
        foreach ($rows as $row) {
            $batch[] = [
                'company_id' => $companyId,
                'whatsapp_message_id' => (int) $message['id'],
                'contact_id' => (int) $row['id'],
                'destination_phone' => (string) $row['phone'],
                'status' => 'pending',
            ];
        }
        $sendModel->insertBatch($batch, null, 500);

        $messageModel = (new WhatsAppMessageModel())->forCompany($companyId);
        $total = (new WhatsAppSendModel())->forCompany($companyId)
            ->where('whatsapp_message_id', (int) $message['id'])->countAllResults();
        $messageModel->update((int) $message['id'], ['total_recipients' => $total]);
    }

    /** @param array<string, mixed> $message */
    private function hasUnexpandedRecipients(array $message, int $companyId): bool
    {
        $listIds = json_decode((string) ($message['list_ids'] ?? '[]'), true);
        if (!is_array($listIds) || $listIds === []) {
            return false;
        }

        return \Config\Database::connect()->table('contacts')
            ->select('contacts.id')
            ->join('contact_list_members', 'contact_list_members.contact_id = contacts.id')
            ->join('contact_lists', 'contact_lists.id = contact_list_members.list_id')
            ->join('whatsapp_sends', 'whatsapp_sends.whatsapp_message_id = ' . (int) $message['id'] . ' AND whatsapp_sends.contact_id = contacts.id', 'left')
            ->where('contacts.company_id', $companyId)
            ->where('contacts.is_active', 1)
            ->where('contacts.opted_out', 0)
            ->where('contacts.bounced', 0)
            ->where('contact_list_members.company_id', $companyId)
            ->where('contact_lists.company_id', $companyId)
            ->whereIn('contact_list_members.list_id', array_map('intval', $listIds))
            ->where('contacts.phone !=', '')
            ->where('whatsapp_sends.id', null)
            ->limit(1)
            ->get()
            ->getRowArray() !== null;
    }
}