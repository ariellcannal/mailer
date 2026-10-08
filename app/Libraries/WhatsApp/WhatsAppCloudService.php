<?php

declare(strict_types=1);

namespace App\Libraries\WhatsApp;

use App\Models\SystemSettingModel;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\URI;
use RuntimeException;

class WhatsAppCloudService
{
    private SystemSettingModel $settings;
    private CURLRequest $http;
    private string $accessToken;
    private string $wabaId;

    public function __construct(int $companyId)
    {
        $this->settings = (new SystemSettingModel())->forCompany($companyId);
        $this->wabaId = trim((string) ($this->setting('whatsapp_waba_id') ?? ''));

        $encryptedToken = (string) ($this->setting('whatsapp_access_token') ?? '');
        if ($this->wabaId === '' || $encryptedToken === '') {
            throw new RuntimeException('Configure o WABA ID e o token de acesso da Meta.');
        }

        $this->accessToken = self::decryptSecret($encryptedToken);
        if ($this->accessToken === '') {
            throw new RuntimeException('O token WhatsApp salvo não pôde ser decifrado. Salve-o novamente.');
        }

        $version = getenv('whatsapp.graphVersion') ?: 'v22.0';
        $baseUri = 'https://graph.facebook.com/' . trim($version, '/') . '/';
        $this->http = new CURLRequest(
            config('App'),
            new URI($baseUri),
            service('response'),
            [
                'baseURI' => $baseUri,
                'timeout' => 15,
                'connect_timeout' => 5,
                'headers' => ['Accept' => 'application/json'],
            ]
        );
    }

    public static function encryptSecret(string $secret): string
    {
        if (trim((string) config('Encryption')->key) === '') {
            throw new RuntimeException('Configure encryption.key antes de salvar credenciais WhatsApp.');
        }

        return service('encrypter')->encrypt($secret);
    }

    /** @return array<string, mixed> */
    public static function exchangeAuthorizationCode(string $code): array
    {
        $appId = trim((string) getenv('whatsapp.appId'));
        $appSecret = trim((string) getenv('whatsapp.appSecret'));
        if ($appId === '' || $appSecret === '') {
            throw new RuntimeException('Configure whatsapp.appId e whatsapp.appSecret no ambiente da aplicação.');
        }

        $version = getenv('whatsapp.graphVersion') ?: 'v22.0';
        $baseUri = 'https://graph.facebook.com/' . trim($version, '/') . '/';
        $http = new CURLRequest(
            config('App'),
            new URI($baseUri),
            service('response'),
            ['baseURI' => $baseUri, 'timeout' => 15, 'connect_timeout' => 5]
        );
        $response = $http->get('oauth/access_token', [
            'query' => [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'code' => $code,
            ],
            'http_errors' => false,
        ]);
        $payload = json_decode((string) $response->getBody(), true);
        $payload = is_array($payload) ? $payload : [];

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300 || empty($payload['access_token'])) {
            throw new RuntimeException((string) ($payload['error']['message'] ?? 'A Meta não autorizou a conexão WhatsApp.'));
        }

        return $payload;
    }

    public static function decryptSecret(string $secret): string
    {
        try {
            return (string) service('encrypter')->decrypt($secret);
        } catch (\Throwable $exception) {
            log_message('error', 'WhatsApp: falha ao decifrar credencial: ' . $exception->getMessage());
            return '';
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function listPhoneNumbers(): array
    {
        $data = $this->request('GET', $this->wabaId . '/phone_numbers', [
            'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,status',
            'limit' => 100,
        ]);

        return is_array($data['data'] ?? null) ? $data['data'] : [];
    }

    /** @return array<int, array<string, mixed>> */
    public function listTemplates(): array
    {
        $data = $this->request('GET', $this->wabaId . '/message_templates', [
            'fields' => 'id,name,language,status,category,components',
            'limit' => 100,
        ]);

        return is_array($data['data'] ?? null) ? $data['data'] : [];
    }

    /** @param array<string, mixed> $template */
    public function submitTemplate(array $template): array
    {
        $components = [];
        $headerType = strtoupper((string) ($template['header_type'] ?? 'NONE'));

        if ($headerType === 'TEXT' && trim((string) ($template['header_content'] ?? '')) !== '') {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $template['header_content'],
            ];
        } elseif (in_array($headerType, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
            $mediaHandle = trim((string) ($template['header_content'] ?? ''));
            if ($mediaHandle === '') {
                throw new RuntimeException('Envie a mídia do cabeçalho antes de submeter o template à Meta.');
            }
            $components[] = [
                'type' => 'HEADER',
                'format' => $headerType,
                'example' => ['header_handle' => [$mediaHandle]],
            ];
        }

        $components[] = [
            'type' => 'BODY',
            'text' => $template['body_content'],
        ];

        if (trim((string) ($template['footer_content'] ?? '')) !== '') {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $template['footer_content'],
            ];
        }

        $buttons = json_decode((string) ($template['buttons'] ?? ''), true);
        if (is_array($buttons) && $buttons !== []) {
            $components[] = ['type' => 'BUTTONS', 'buttons' => $buttons];
        }

        return $this->request('POST', $this->wabaId . '/message_templates', [], [
            'name' => $template['name'],
            'language' => $template['language'] ?: 'pt_BR',
            'category' => strtoupper((string) ($template['category'] ?? 'UTILITY')),
            'components' => $components,
        ]);
    }

    public function uploadTemplateMedia(string $filePath, string $mimeType): string
    {
        $appId = trim((string) getenv('whatsapp.appId'));
        if ($appId === '') {
            throw new RuntimeException('Configure o Meta App ID antes de enviar mídia para templates.');
        }
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException('O arquivo de mídia não pôde ser lido.');
        }

        $fileSize = filesize($filePath);
        $upload = $this->request('POST', $appId . '/uploads', [
            'file_length' => $fileSize,
            'file_type' => $mimeType,
        ]);
        $uploadId = (string) ($upload['id'] ?? '');
        if ($uploadId === '') {
            throw new RuntimeException('A Meta não retornou uma sessão de upload.');
        }

        $response = $this->http->request('POST', $uploadId, [
            'headers' => [
                'Authorization' => 'OAuth ' . $this->accessToken,
                'file_offset' => '0',
                'Content-Type' => $mimeType,
            ],
            'body' => file_get_contents($filePath),
            'http_errors' => false,
        ]);
        $result = json_decode((string) $response->getBody(), true);
        $result = is_array($result) ? $result : [];
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300 || empty($result['h'])) {
            throw new RuntimeException((string) ($result['error']['message'] ?? 'A Meta não aceitou o upload da mídia.'));
        }

        return (string) $result['h'];
    }

    /** @param array<string, mixed> $template @return array<string, array<int, string>> */
    public static function templateVariables(array $template): array
    {
        $variables = [];
        if (strtoupper((string) ($template['header_type'] ?? '')) === 'TEXT') {
            $headerVariables = self::variableNames((string) ($template['header_content'] ?? ''));
            if ($headerVariables !== []) {
                $variables['header'] = $headerVariables;
            }
        }

        $bodyVariables = self::variableNames((string) ($template['body_content'] ?? ''));
        if ($bodyVariables !== []) {
            $variables['body'] = $bodyVariables;
        }

        return $variables;
    }

    /** @param array<string, mixed> $template @param array<string, mixed> $mapping @return array<string, array<string, array<string, string>>> */
    public static function normalizeVariableMapping(array $template, array $mapping): array
    {
        $normalized = [];
        $contactFields = ['name', 'nickname', 'email', 'phone'];

        foreach (self::templateVariables($template) as $component => $variables) {
            $componentMapping = $mapping[$component] ?? [];
            if (!is_array($componentMapping)) {
                throw new \InvalidArgumentException('O mapeamento das variáveis do template está inválido.');
            }

            foreach ($variables as $variable) {
                $entry = $componentMapping[$variable] ?? null;
                if (!is_array($entry)) {
                    throw new \InvalidArgumentException('Configure a variável {{' . $variable . '}} do ' . ($component === 'header' ? 'cabeçalho' : 'corpo') . '.');
                }

                $source = (string) ($entry['source'] ?? '');
                if ($source === 'contact') {
                    $field = (string) ($entry['field'] ?? '');
                    if (!in_array($field, $contactFields, true)) {
                        throw new \InvalidArgumentException('Selecione um campo válido do contato para a variável {{' . $variable . '}}.');
                    }
                    $normalized[$component][$variable] = ['source' => 'contact', 'field' => $field];
                    continue;
                }

                $value = trim((string) ($entry['value'] ?? ''));
                if ($source !== 'text' || $value === '') {
                    throw new \InvalidArgumentException('Informe um texto fixo para a variável {{' . $variable . '}}.');
                }
                $normalized[$component][$variable] = ['source' => 'text', 'value' => $value];
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $number
     * @param array<string, mixed> $template
     * @param array<string, mixed> $contact
     * @return array<string, mixed>
     */
    public function sendTemplate(array $number, array $template, array $contact, string $destination, array $variableMapping = []): array
    {
        if (strtoupper((string) ($template['status'] ?? '')) !== 'APPROVED') {
            throw new RuntimeException('A Meta ainda não aprovou este template.');
        }

        $components = [];
        $legacyFallbacks = [
            (string) ($contact['name'] ?? ''),
            (string) ($contact['nickname'] ?? ''),
            (string) ($contact['email'] ?? ''),
            (string) ($contact['phone'] ?? ''),
        ];
        $legacyIndex = 0;
        $templateComponents = [];
        if (strtoupper((string) ($template['header_type'] ?? '')) === 'TEXT') {
            $templateComponents['header'] = (string) ($template['header_content'] ?? '');
        }
        $templateComponents['body'] = (string) ($template['body_content'] ?? '');

        foreach ($templateComponents as $componentName => $content) {
            $variables = self::variableNames($content);
            if ($variables === []) {
                continue;
            }

            $parameters = [];
            foreach ($variables as $variable) {
                $entry = $variableMapping[$componentName][$variable] ?? null;
                if (($entry['source'] ?? '') === 'text') {
                    $value = (string) ($entry['value'] ?? '');
                } elseif (($entry['source'] ?? '') === 'contact') {
                    $value = (string) ($contact[$entry['field'] ?? ''] ?? '');
                    if (trim($value) === '') {
                        throw new RuntimeException('O campo selecionado para a variável {{' . $variable . '}} está vazio neste contato.');
                    }
                } else {
                    $value = $legacyFallbacks[$legacyIndex] ?? '';
                }

                $parameter = ['type' => 'text', 'text' => $value];
                if (!ctype_digit($variable)) {
                    $parameter['parameter_name'] = $variable;
                }
                $parameters[] = $parameter;
                $legacyIndex++;
            }
            $components[] = ['type' => $componentName, 'parameters' => $parameters];
        }

        return $this->request('POST', $number['phone_number_id'] . '/messages', [], [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => preg_replace('/\D+/', '', $destination),
            'type' => 'template',
            'template' => [
                'name' => $template['name'],
                'language' => ['code' => $template['language'] ?: 'pt_BR'],
                'components' => $components,
            ],
        ]);
    }

    /** @param array<string, mixed> $query @param array<string, mixed>|null $body @return array<string, mixed> */
    private function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        $options = [
            'headers' => ['Authorization' => 'Bearer ' . $this->accessToken],
            'http_errors' => false,
        ];
        if ($query !== []) {
            $options['query'] = $query;
        }
        if ($body !== null) {
            $options['json'] = $body;
        }

        $response = $this->http->request($method, ltrim($path, '/'), $options);
        $payload = json_decode((string) $response->getBody(), true);
        $payload = is_array($payload) ? $payload : [];

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $message = $payload['error']['message'] ?? 'A API da Meta retornou HTTP ' . $response->getStatusCode();
            throw new RuntimeException((string) $message);
        }

        return $payload;
    }

    private function setting(string $key): ?string
    {
        $setting = $this->settings->getByKey($key);

        return $setting === null ? null : (string) ($setting['setting_value'] ?? '');
    }

    /** @return array<int, string> */
    private static function variableNames(string $content): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $content, $matches);
        $variables = array_values(array_unique($matches[1] ?? []));
        if ($variables !== [] && count(array_filter($variables, 'ctype_digit')) === count($variables)) {
            usort($variables, static fn (string $left, string $right): int => (int) $left <=> (int) $right);
        }

        return $variables;
    }
}