<?php

namespace App\Libraries\AWS;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

/**
 * AWS SES Service
 * 
 * Serviço para integração com Amazon SES
 * Gerencia envio de emails, verificação de domínios e consulta de limites
 * 
 * @package App\Libraries\AWS
 * @author  Mailer System
 * @version 1.0.0
 */
class SESService
{
    /**
     * Cliente AWS SES
     * 
     * @var SesClient
     */
    protected $client;

    /**
     * Região AWS
     * 
     * @var string
     */
    protected $region;

    /**
     * Configuration Set
     * 
     * @var string|null
     */
    protected $configurationSet;

    /**
     * Construtor
     * 
     * Inicializa o cliente AWS SES com as credenciais configuradas
     * 
     * @throws \Exception Se credenciais não estiverem configuradas
     */
    public function __construct()
    {
        $this->region = getenv('aws.ses.region') ?: 'us-east-1';
        $accessKey = getenv('aws.ses.accessKey');
        $secretKey = getenv('aws.ses.secretKey');
        $this->configurationSet = getenv('aws.ses.configurationSet');

        if (empty($accessKey) || empty($secretKey)) {
            throw new \Exception('AWS SES credentials not configured');
        }

        $this->client = new SesClient([
            'version' => 'latest',
            'region'  => $this->region,
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);
    }

    /**
     * Envia um email via AWS SES
     * 
     * @param string $from      Email do remetente
     * @param string $fromName  Nome do remetente
     * @param string $to        Email do destinatário
     * @param string $subject   Assunto do email
     * @param string $htmlBody  Corpo do email em HTML
     * @param string|null $replyTo Email de resposta
     * @param array  $tags      Tags para rastreamento
     * 
     * @return array Resultado do envio com MessageId
     * @throws AwsException Se houver erro no envio
     */
    public function sendEmail(
        string $from,
        string $fromName,
        string $to,
        string $subject,
        string $htmlBody,
        ?string $replyTo = null,
        array $tags = []
    ): array {
        $params = [
            'Source' => "$fromName <$from>",
            'Destination' => [
                'ToAddresses' => [$to],
            ],
            'Message' => [
                'Subject' => [
                    'Data' => $subject,
                    'Charset' => 'UTF-8',
                ],
                'Body' => [
                    'Html' => [
                        'Data' => $htmlBody,
                        'Charset' => 'UTF-8',
                    ],
                ],
            ],
        ];

        if ($replyTo) {
            $params['ReplyToAddresses'] = [$replyTo];
        }

        if ($this->configurationSet) {
            $params['ConfigurationSetName'] = $this->configurationSet;
        }

        if (!empty($tags)) {
            $params['Tags'] = $tags;
        }

        try {
            $result = $this->client->sendEmail($params);
            
            return [
                'success' => true,
                'messageId' => $result['MessageId'],
                'requestId' => $result['@metadata']['requestId'],
            ];
        } catch (AwsException $e) {
            log_message('error', 'AWS SES Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
            ];
        }
    }

    /**
     * Verifica um email no AWS SES
     * 
     * @param string $email Email a ser verificado
     * 
     * @return array Resultado da verificação
     * @throws AwsException Se houver erro
     */
    public function verifyEmail(string $email): array
    {
        try {
            $result = $this->client->verifyEmailIdentity([
                'EmailAddress' => $email,
            ]);

            return [
                'success' => true,
                'message' => 'Verification email sent to ' . $email,
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica um domínio no AWS SES
     * 
     * @param string $domain Domínio a ser verificado
     * 
     * @return array Resultado com token de verificação
     * @throws AwsException Se houver erro
     */
    public function verifyDomain(string $domain): array
    {
        try {
            $result = $this->client->verifyDomainIdentity([
                'Domain' => $domain,
            ]);

            return [
                'success' => true,
                'verificationToken' => $result['VerificationToken'],
                'message' => 'Add TXT record: _amazonses.' . $domain . ' = ' . $result['VerificationToken'],
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Habilita DKIM para um domínio
     * 
     * @param string $domain Domínio
     * 
     * @return array Resultado com tokens DKIM
     * @throws AwsException Se houver erro
     */
    public function enableDKIM(string $domain): array
    {
        try {
            $result = $this->client->verifyDomainDkim([
                'Domain' => $domain,
            ]);

            return [
                'success' => true,
                'dkimTokens' => array_values(array_filter(
                    $result['DkimTokens'],
                    static fn($token): bool => is_string($token) && $token !== ''
                )),
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtém atributos DKIM de uma identidade já configurada.
     *
     * @param string $identity Identidade (domínio ou email) consultada.
     *
     * @return array Dados contendo status, tokens e habilitação.
     */
    public function getIdentityDkimAttributes(string $identity): array
    {
        try {
            $result = $this->client->getIdentityDkimAttributes([
                'Identities' => [$identity],
            ]);

            $attributes = $result['DkimAttributes'][$identity] ?? null;

            if ($attributes === null) {
                return [
                    'success' => false,
                    'message' => 'Identidade não encontrada na AWS SES.',
                ];
            }

            return [
                'success' => true,
                'enabled' => (bool) ($attributes['DkimEnabled'] ?? false),
                'status' => $attributes['DkimVerificationStatus'] ?? null,
                'tokens' => array_values(array_filter(
                    $attributes['DkimTokens'] ?? [],
                    static fn($token): bool => is_string($token) && $token !== ''
                )),
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'message' => 'Falha ao obter atributos DKIM: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtém status de verificação de uma identidade
     *
     * @param string $identity Email ou domínio
     *
     * @return array Status de verificação
     * @throws AwsException Se houver erro
     */
    public function getIdentityVerificationStatus(string $identity): array
    {
        try {
            $result = $this->client->getIdentityVerificationAttributes([
                'Identities' => [$identity],
            ]);

            $status = $result['VerificationAttributes'][$identity] ?? null;

            if (!$status) {
                return [
                    'verified' => false,
                    'status' => 'NotFound',
                ];
            }

            return [
                'verified' => $status['VerificationStatus'] === 'Success',
                'status' => $status['VerificationStatus'],
                'token' => $status['VerificationToken'] ?? null,
            ];
        } catch (AwsException $e) {
            return [
                'verified' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtém limites de envio da conta SES
     * 
     * @return array Limites de envio (24h, por segundo)
     * @throws AwsException Se houver erro
     */
    public function getSendQuota(): array
    {
        try {
            $result = $this->client->getSendQuota();

            return [
                'success' => true,
                'max24HourSend' => $result['Max24HourSend'],
                'maxSendRate' => $result['MaxSendRate'],
                'sentLast24Hours' => $result['SentLast24Hours'],
                'remaining' => $result['Max24HourSend'] - $result['SentLast24Hours'],
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Não foi possível obter os limites no momento. ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtém estatísticas de envio
     * 
     * @return array Estatísticas (bounces, complaints, deliveries)
     * @throws AwsException Se houver erro
     */
    public function getSendStatistics(): array
    {
        try {
            $result = $this->client->getSendStatistics();

            return [
                'success' => true,
                'dataPoints' => $result['SendDataPoints'],
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Lista todas as identidades verificadas
     * 
     * @param string $type Tipo: 'EmailAddress' ou 'Domain'
     * 
     * @return array Lista de identidades
     * @throws AwsException Se houver erro
     */
    public function listIdentities(string $type = 'EmailAddress'): array
    {
        try {
            $result = $this->client->listIdentities([
                'IdentityType' => $type,
                'MaxItems' => 100,
            ]);

            return [
                'success' => true,
                'identities' => $result['Identities'],
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove uma identidade verificada
     * 
     * @param string $identity Email ou domínio
     * 
     * @return array Resultado da remoção
     * @throws AwsException Se houver erro
     */
    public function deleteIdentity(string $identity): array
    {
        try {
            $this->client->deleteIdentity([
                'Identity' => $identity,
            ]);

            return [
                'success' => true,
                'message' => 'Identity deleted successfully',
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
