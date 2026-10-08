<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\WhatsApp\WhatsAppCloudService;
use App\Models\CampaignModel;
use App\Models\ContactListModel;
use App\Models\SystemSettingModel;
use App\Models\WhatsAppMessageModel;
use App\Models\WhatsAppNumberModel;
use App\Models\WhatsAppTemplateModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class WhatsAppController extends BaseController
{
    public function index(): string
    {
        $companyId = (int) session()->get('company_id');
        $messages = \Config\Database::connect()->table('whatsapp_messages')
            ->select('whatsapp_messages.*, campaigns.name AS campaign_name, whatsapp_templates.name AS template_name, whatsapp_numbers.display_phone_number')
            ->join('campaigns', 'campaigns.id = whatsapp_messages.campaign_id')
            ->join('whatsapp_templates', 'whatsapp_templates.id = whatsapp_messages.template_id')
            ->join('whatsapp_numbers', 'whatsapp_numbers.id = whatsapp_messages.whatsapp_number_id')
            ->where('whatsapp_messages.company_id', $companyId)
            ->orderBy('whatsapp_messages.created_at', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        return view('whatsapp/index', [
            'messages' => $messages,
            'activeMenu' => 'whatsapp',
            'pageTitle' => 'Envios WhatsApp',
        ]);
    }

    public function create(): string
    {
        $templates = (new WhatsAppTemplateModel())->where('status', 'approved')->orderBy('name')->findAll();
        $numbers = (new WhatsAppNumberModel())->where('is_active', 1)->orderBy('display_phone_number')->findAll();

        return view('whatsapp/send', [
            'campaigns' => (new CampaignModel())->orderBy('name')->findAll(),
            'contactLists' => (new ContactListModel())->orderBy('name')->findAll(),
            'templates' => $templates,
            'numbers' => $numbers,
            'selectedCampaignId' => (int) $this->request->getGet('campaign_id'),
            'activeMenu' => 'whatsapp',
            'pageTitle' => 'Novo Envio WhatsApp',
        ]);
    }

    public function store(): RedirectResponse
    {
        $campaignId = (int) $this->request->getPost('campaign_id');
        $templateId = (int) $this->request->getPost('template_id');
        $numberId = (int) $this->request->getPost('whatsapp_number_id');
        $listIds = array_values(array_unique(array_map('intval', (array) $this->request->getPost('contact_lists'))));
        $template = (new WhatsAppTemplateModel())->where('status', 'approved')->find($templateId);

        if (!(new CampaignModel())->find($campaignId)
            || !$template
            || !(new WhatsAppNumberModel())->where('is_active', 1)->find($numberId)
            || $listIds === []) {
            return redirect()->back()->withInput()->with('error', 'Selecione uma campanha, template aprovado, número conectado e listas válidas.');
        }

        $postedMapping = json_decode((string) $this->request->getPost('variable_mapping'), true);
        if (!is_array($postedMapping)) {
            return redirect()->back()->withInput()->with('error', 'Configure as variáveis do template antes de criar o envio.');
        }
        try {
            $variableMapping = WhatsAppCloudService::normalizeVariableMapping($template, $postedMapping);
        } catch (\InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        $validLists = (new ContactListModel())->whereIn('id', $listIds)->findColumn('id') ?? [];
        if (count($validLists) !== count($listIds)) {
            return redirect()->back()->withInput()->with('error', 'Uma ou mais listas não pertencem a esta empresa.');
        }

        $eligibleContact = (new \App\Models\ContactModel())->builder()
            ->select('contacts.id')
            ->join('contact_list_members', 'contact_list_members.contact_id = contacts.id')
            ->where('contact_list_members.company_id', (int) session()->get('company_id'))
            ->whereIn('contact_list_members.list_id', $validLists)
            ->where('contacts.phone !=', '')
            ->where('contacts.is_active', 1)
            ->where('contacts.opted_out', 0)
            ->where('contacts.bounced', 0)
            ->limit(1)
            ->get()->getRowArray();
        if (!$eligibleContact) {
            return redirect()->back()->withInput()->with('error', 'As listas selecionadas não contêm contatos ativos com telefone WhatsApp válido.');
        }

        $scheduledInput = trim((string) $this->request->getPost('scheduled_at'));
        $scheduledTimestamp = $scheduledInput !== '' ? strtotime($scheduledInput) : time();
        if ($scheduledTimestamp === false) {
            return redirect()->back()->withInput()->with('error', 'Informe uma data de agendamento válida.');
        }
        $scheduledAt = date('Y-m-d H:i:s', $scheduledTimestamp);

        $messageModel = new WhatsAppMessageModel();
        $messageId = $messageModel->insert([
            'campaign_id' => $campaignId,
            'template_id' => $templateId,
            'whatsapp_number_id' => $numberId,
            'name' => trim((string) $this->request->getPost('name')) ?: 'Envio WhatsApp',
            'list_ids' => json_encode($validLists),
            'variable_mapping' => json_encode($variableMapping, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'scheduled_at' => $scheduledAt,
            'status' => strtotime($scheduledAt) > time() ? 'scheduled' : 'queued',
        ]);

        if (!$messageId) {
            return redirect()->back()->withInput()->with('error', 'Não foi possível criar o envio.');
        }

        return redirect()->to('/whatsapp')->with('success', 'Envio adicionado à fila assíncrona.');
    }

    public function templates(): string
    {
        return view('whatsapp/templates', [
            'templates' => (new WhatsAppTemplateModel())->orderBy('updated_at', 'DESC')->findAll(),
            'activeMenu' => 'whatsapp',
            'pageTitle' => 'Templates do WhatsApp',
        ]);
    }

    public function createTemplate(): string
    {
        return view('whatsapp/template_form', [
            'template' => null,
            'activeMenu' => 'whatsapp',
            'pageTitle' => 'Novo Template WhatsApp',
        ]);
    }

    public function editTemplate(int $id): RedirectResponse|string
    {
        $template = (new WhatsAppTemplateModel())->find($id);
        if (!$template) {
            return redirect()->to('/whatsapp/templates')->with('error', 'Template não encontrado.');
        }

        return view('whatsapp/template_form', [
            'template' => $template,
            'activeMenu' => 'whatsapp',
            'pageTitle' => 'Editar template WhatsApp',
        ]);
    }

    public function storeTemplate(): RedirectResponse
    {
        $name = strtolower(trim((string) $this->request->getPost('name')));
        $body = trim((string) $this->request->getPost('body_content'));
        $templateId = (int) ($this->request->getPost('template_id') ?? 0);
        $headerType = strtoupper((string) ($this->request->getPost('header_type') ?: 'NONE'));
        $model = new WhatsAppTemplateModel();
        $existingTemplate = $templateId > 0 ? $model->find($templateId) : null;

        if ($templateId > 0 && !$existingTemplate) {
            return redirect()->to('/whatsapp/templates')->with('error', 'Template não encontrado.');
        }

        if (!preg_match('/^[a-z0-9_]{3,128}$/', $name) || $body === '') {
            return redirect()->back()->withInput()->with('error', 'Use um nome em letras minúsculas, números e underline; o corpo é obrigatório.');
        }

        $headerContent = trim((string) $this->request->getPost('header_content')) ?: null;
        if (in_array($headerType, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
            $mediaFile = $this->request->getFile('header_media');
            if ($mediaFile && $mediaFile->isValid() && !$mediaFile->hasMoved()) {
                if ($mediaFile->getSize() > 5 * 1024 * 1024 || !in_array($mediaFile->getMimeType(), ['image/jpeg', 'image/png'], true)) {
                    return redirect()->back()->withInput()->with('error', 'A mídia deve ser JPEG ou PNG com até 5 MB.');
                }

                try {
                    $headerContent = (new WhatsAppCloudService((int) session()->get('company_id')))
                        ->uploadTemplateMedia($mediaFile->getTempName(), $mediaFile->getMimeType());
                } catch (\Throwable $exception) {
                    return redirect()->back()->withInput()->with('error', 'Não foi possível enviar a mídia à Meta: ' . $exception->getMessage());
                }
            } elseif (empty($existingTemplate['header_content'] ?? null)) {
                return redirect()->back()->withInput()->with('error', 'Selecione uma imagem de cabeçalho.');
            } else {
                $headerContent = $existingTemplate['header_content'];
            }
        }

        $data = [
            'name' => $name,
            'language' => (string) ($this->request->getPost('language') ?: 'pt_BR'),
            'category' => (string) ($this->request->getPost('category') ?: 'UTILITY'),
            'header_type' => $headerType,
            'header_content' => $headerContent,
            'body_content' => $body,
            'footer_content' => trim((string) $this->request->getPost('footer_content')) ?: null,
            'buttons' => trim((string) $this->request->getPost('buttons')) ?: null,
            'status' => 'draft',
        ];

        if ($templateId > 0) {
            $model->update($templateId, $data);
        } else {
            $templateId = (int) $model->insert($data);
        }

        return redirect()->to('/whatsapp/templates')->with('success', 'Rascunho do template salvo. Sincronize os templates aprovados pela Meta para habilitá-los nos envios.');
    }

    public function submitTemplate(int $id): RedirectResponse
    {
        $model = new WhatsAppTemplateModel();
        $template = $model->find($id);
        if (!$template) {
            return redirect()->to('/whatsapp/templates')->with('error', 'Template não encontrado.');
        }

        try {
            $result = (new WhatsAppCloudService((int) session()->get('company_id')))->submitTemplate($template);
            $model->update($id, [
                'meta_template_id' => $result['id'] ?? null,
                'status' => strtolower((string) ($result['status'] ?? 'pending')),
            ]);
            return redirect()->to('/whatsapp/templates')->with('success', 'Template enviado à Meta para análise.');
        } catch (\Throwable $exception) {
            log_message('error', 'WhatsApp template submission: ' . $exception->getMessage());
            return redirect()->to('/whatsapp/templates')->with('error', $exception->getMessage());
        }
    }

    public function syncTemplates(): RedirectResponse
    {
        try {
            $service = new WhatsAppCloudService((int) session()->get('company_id'));
            $remoteTemplates = $service->listTemplates();
            $model = new WhatsAppTemplateModel();

            foreach ($remoteTemplates as $remote) {
                $components = $remote['components'] ?? [];
                $header = $this->findTemplateComponent($components, 'HEADER');
                $body = $this->findTemplateComponent($components, 'BODY');
                $footer = $this->findTemplateComponent($components, 'FOOTER');
                $buttons = $this->findTemplateComponent($components, 'BUTTONS');
                $data = [
                    'name' => $remote['name'] ?? '',
                    'language' => $remote['language'] ?? 'pt_BR',
                    'category' => $remote['category'] ?? 'UTILITY',
                    'header_type' => $header['format'] ?? 'NONE',
                    'header_content' => $header['text'] ?? ($header['example']['header_handle'][0] ?? null),
                    'body_content' => $body['text'] ?? '',
                    'footer_content' => $footer['text'] ?? null,
                    'buttons' => isset($buttons['buttons']) ? json_encode($buttons['buttons']) : null,
                    'meta_template_id' => $remote['id'] ?? null,
                    'status' => strtolower((string) ($remote['status'] ?? 'pending')),
                ];
                $existing = $model->where('name', $data['name'])->where('language', $data['language'])->first();
                if ($existing) {
                    $model->update((int) $existing['id'], $data);
                } else {
                    $model->insert($data);
                }
            }

            return redirect()->to('/whatsapp/templates')->with('success', count($remoteTemplates) . ' templates sincronizados com a Meta.');
        } catch (\Throwable $exception) {
            log_message('error', 'WhatsApp template sync: ' . $exception->getMessage());
            return redirect()->to('/whatsapp/templates')->with('error', $exception->getMessage());
        }
    }

    public function settings(): string
    {
        $settings = new SystemSettingModel();
        $numbers = (new WhatsAppNumberModel())->orderBy('display_phone_number')->findAll();

        return view('whatsapp/settings', [
            'wabaId' => $settings->getByKey('whatsapp_waba_id')['setting_value'] ?? '',
            'hasAccessToken' => !empty($settings->getByKey('whatsapp_access_token')['setting_value'] ?? null),
            'metaAppId' => (string) getenv('whatsapp.appId'),
            'metaConfigId' => (string) getenv('whatsapp.configId'),
            'graphVersion' => (string) (getenv('whatsapp.graphVersion') ?: 'v22.0'),
            'numbers' => $numbers,
            'activeMenu' => 'whatsapp-settings',
            'pageTitle' => 'WhatsApp - Configurações',
        ]);
    }

    public function connectMeta(): ResponseInterface
    {
        $code = trim((string) $this->request->getPost('code'));
        $wabaId = trim((string) $this->request->getPost('waba_id'));
        $selectedPhoneNumberId = trim((string) $this->request->getPost('phone_number_id'));
        if ($code === '' || $wabaId === '' || $selectedPhoneNumberId === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'A Meta não retornou os dados completos da conta e do número.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        try {
            $authorization = WhatsAppCloudService::exchangeAuthorizationCode($code);
            $token = (string) ($authorization['access_token'] ?? '');
            if ($token === '') {
                throw new RuntimeException('A Meta não retornou um token de acesso.');
            }

            $settings = new SystemSettingModel();
            $settings->upsertSetting('whatsapp_access_token', WhatsAppCloudService::encryptSecret($token));
            $settings->upsertSetting('whatsapp_waba_id', $wabaId);

            $numbers = (new WhatsAppCloudService((int) session()->get('company_id')))->listPhoneNumbers();
            $numberFound = false;
            foreach ($numbers as $number) {
                if ((string) ($number['id'] ?? '') === $selectedPhoneNumberId) {
                    $numberFound = true;
                }
            }
            $this->storePhoneNumbers($numbers, $wabaId);

            if (!$numberFound) {
                throw new RuntimeException('A conta foi autorizada, mas o número selecionado não apareceu na lista da Meta.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'WhatsApp conectado. Números e qualidade sincronizados.',
                'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $exception) {
            log_message('error', 'WhatsApp Embedded Signup: ' . $exception->getMessage());
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $exception->getMessage(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }

    public function syncNumbers(): RedirectResponse
    {
        try {
            $numbers = (new WhatsAppCloudService((int) session()->get('company_id')))->listPhoneNumbers();
            $count = $this->storePhoneNumbers($numbers, (string) ($this->settingValue('whatsapp_waba_id') ?? ''));
            return redirect()->to('/settings/whatsapp')->with('success', $count . ' números sincronizados.');
        } catch (\Throwable $exception) {
            log_message('error', 'WhatsApp numbers sync: ' . $exception->getMessage());
            return redirect()->to('/settings/whatsapp')->with('error', $exception->getMessage());
        }
    }

    /** @param array<int, array<string, mixed>> $components @return array<string, mixed> */
    private function findTemplateComponent(array $components, string $type): array
    {
        foreach ($components as $component) {
            if (($component['type'] ?? '') === $type) {
                return $component;
            }
        }

        return [];
    }

    private function settingValue(string $key): ?string
    {
        $setting = (new SystemSettingModel())->getByKey($key);
        return $setting === null ? null : (string) ($setting['setting_value'] ?? '');
    }

    /** @param array<int, array<string, mixed>> $numbers */
    private function storePhoneNumbers(array $numbers, string $wabaId): int
    {
        $model = new WhatsAppNumberModel();
        $count = 0;
        foreach ($numbers as $number) {
            $id = (string) ($number['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $data = [
                'waba_id' => $wabaId,
                'phone_number_id' => $id,
                'display_phone_number' => (string) ($number['display_phone_number'] ?? ''),
                'verified_name' => $number['verified_name'] ?? null,
                'quality_rating' => $number['quality_rating'] ?? null,
                'status' => $number['status'] ?? $number['code_verification_status'] ?? 'CONNECTED',
                'is_active' => 1,
                'synced_at' => date('Y-m-d H:i:s'),
            ];
            $existing = $model->where('phone_number_id', $id)->first();
            if ($existing) {
                $model->update((int) $existing['id'], $data);
            } else {
                $model->insert($data);
            }
            $count++;
        }

        return $count;
    }
}