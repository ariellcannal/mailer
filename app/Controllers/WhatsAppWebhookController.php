<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\WhatsAppMessageModel;
use App\Models\WhatsAppSendModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class WhatsAppWebhookController extends Controller
{
    public function verify(): ResponseInterface
    {
        $mode = $this->queryParameter('hub.mode');
        $token = $this->queryParameter('hub.verify_token');
        $challenge = $this->queryParameter('hub.challenge');
        $expected = (string) getenv('whatsapp.webhookVerifyToken');

        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
            return $this->response->setStatusCode(200)->setBody($challenge);
        }

        return $this->response->setStatusCode(403)->setBody('Forbidden');
    }

    public function receive(): ResponseInterface
    {
        $secret = (string) getenv('whatsapp.appSecret');
        $signature = $this->request->getHeaderLine('X-Hub-Signature-256');
        $body = (string) $this->request->getBody();
        if ($secret === '' || !str_starts_with($signature, 'sha256=')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $body, $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false]);
        }

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $phoneNumberId = (string) ($value['metadata']['phone_number_id'] ?? '');
                if ($phoneNumberId === '') {
                    continue;
                }

                $number = \Config\Database::connect()->table('whatsapp_numbers')
                    ->where('phone_number_id', $phoneNumberId)
                    ->get()->getRowArray();
                if (!$number) {
                    continue;
                }

                foreach (($value['statuses'] ?? []) as $event) {
                    $this->applyStatus((int) $number['company_id'], $event);
                }
            }
        }

        return $this->response->setStatusCode(200)->setJSON(['success' => true]);
    }

    /** @param array<string, mixed> $event */
    private function applyStatus(int $companyId, array $event): void
    {
        $metaMessageId = (string) ($event['id'] ?? '');
        $status = strtolower((string) ($event['status'] ?? ''));
        if ($metaMessageId === '' || !in_array($status, ['sent', 'delivered', 'read', 'failed'], true)) {
            return;
        }

        $sendModel = (new WhatsAppSendModel())->forCompany($companyId);
        $send = $sendModel->where('meta_message_id', $metaMessageId)->first();
        if (!$send || $send['status'] === $status) {
            return;
        }

        $timestamp = isset($event['timestamp']) ? date('Y-m-d H:i:s', (int) $event['timestamp']) : date('Y-m-d H:i:s');
        $update = ['status' => $status];
        if ($status === 'delivered' || $status === 'read') {
            $update['delivered_at'] = $timestamp;
        }
        if ($status === 'read') {
            $update['read_at'] = $timestamp;
        }
        if ($status === 'failed') {
            $error = $event['errors'][0] ?? [];
            $update['error_code'] = isset($error['code']) ? (string) $error['code'] : null;
            $update['error_message'] = $error['title'] ?? $error['message'] ?? 'Falha retornada pela Meta.';
        }
        $sendModel->update((int) $send['id'], $update);

        $totals = \Config\Database::connect()->table('whatsapp_sends')
            ->select("SUM(status IN ('sent','delivered','read')) AS sent, SUM(status IN ('delivered','read')) AS delivered, SUM(status = 'read') AS `read`, SUM(status = 'failed') AS failed", false)
            ->where('company_id', $companyId)
            ->where('whatsapp_message_id', (int) $send['whatsapp_message_id'])
            ->get()->getRowArray() ?? [];

        (new WhatsAppMessageModel())->forCompany($companyId)->update((int) $send['whatsapp_message_id'], [
            'total_sent' => (int) ($totals['sent'] ?? 0),
            'total_delivered' => (int) ($totals['delivered'] ?? 0),
            'total_read' => (int) ($totals['read'] ?? 0),
            'total_failed' => (int) ($totals['failed'] ?? 0),
        ]);
    }

    private function queryParameter(string $name): string
    {
        foreach (explode('&', (string) $this->request->getUri()->getQuery()) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            if (rawurldecode($key) === $name) {
                return rawurldecode(str_replace('+', ' ', $value));
            }
        }

        return '';
    }
}