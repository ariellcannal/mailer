<?php

declare(strict_types=1);

namespace App\Libraries\AWS;

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;

/**
 * Serviço para configurar notificações de bounce SES → SNS → SQS.
 */
class BounceNotificationService
{
    /**
     * Cliente SES utilizado para vincular notificações.
     *
     * @var SesClient
     */
    protected SesClient $sesClient;

    /**
     * Cliente SNS responsável pelo tópico de bounces.
     *
     * @var SnsClient
     */
    protected SnsClient $snsClient;

    /**
     * Cliente SQS para manipular a fila de bounces.
     *
     * @var SqsClient
     */
    protected SqsClient $sqsClient;

    /**
     * Região AWS aplicada aos serviços.
     *
     * @var string
     */
    protected string $region;

    /**
     * Construtor.
     *
     * @throws \Exception Quando credenciais AWS não estão configuradas.
     */
    public function __construct()
    {
        $this->region = getenv('aws.ses.region');
        $accessKey = getenv('aws.ses.accessKey');
        $secretKey = getenv('aws.ses.secretKey');

        if (empty($accessKey) || empty($secretKey)) {
            throw new \Exception('AWS credentials not configured');
        }

        $config = [
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
        ];

        $environment = getenv('CI_ENVIRONMENT') ?: 'production';
        if (strtolower($environment) === 'development') {
            $config['http'] = [
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ],
            ];
        }

        $this->sesClient = new SesClient($config);
        $this->snsClient = new SnsClient($config);
        $this->sqsClient = new SqsClient($config);
    }

    /**
     * Garante que o fluxo SES → SNS → SQS esteja configurado.
     *
     * @param string $identity Identidade (domínio ou email) utilizada no SES.
     *
     * @return array<string, mixed> Resultado do provisionamento.
     */
    public function ensureBounceFlow(string $identity): array
    {
        try {
            $normalizedIdentity = $this->normalizeIdentity($identity);
            $topicName = getenv('aws.sns.bounceTopic') ?: 'mailer-bounce-' . $normalizedIdentity;
            $queueName = getenv('aws.sqs.bounceQueue') ?: 'mailer-bounce-' . $normalizedIdentity;

            $topicArn = $this->createTopic($topicName);
            $queueUrl = $this->createQueue($queueName);
            $queueArn = $this->getQueueArn($queueUrl);

            $this->allowSnsOnQueue($queueUrl, $queueArn, $topicArn);
            $this->subscribeQueueToTopic($queueArn, $topicArn);
            $this->bindSesIdentity($identity, $topicArn);

            return [
                'success' => true,
                'topicArn' => $topicArn,
                'queueUrl' => $queueUrl,
                'queueArn' => $queueArn,
            ];
        } catch (AwsException $exception) {
            log_message('error', 'Erro AWS ao configurar bounces: ' . $exception->getMessage());

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        } catch (\Throwable $exception) {
            log_message('error', 'Erro ao configurar fluxo de bounces: ' . $exception->getMessage());

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Cria (ou obtém) o tópico SNS para bounces.
     *
     * @param string $name Nome do tópico.
     *
     * @return string ARN do tópico criado.
     */
    protected function createTopic(string $name): string
    {
        $result = $this->snsClient->createTopic(['Name' => $name]);

        return (string) $result['TopicArn'];
    }

    /**
     * Cria (ou obtém) a fila SQS para bounces.
     *
     * @param string $name Nome da fila.
     *
     * @return string URL da fila criada.
     */
    protected function createQueue(string $name): string
    {
        $result = $this->sqsClient->createQueue(['QueueName' => $name]);

        return (string) $result['QueueUrl'];
    }

    /**
     * Obtém o ARN da fila SQS criada.
     *
     * @param string $queueUrl URL da fila.
     *
     * @return string ARN associado.
     */
    protected function getQueueArn(string $queueUrl): string
    {
        $attributes = $this->sqsClient->getQueueAttributes([
            'QueueUrl' => $queueUrl,
            'AttributeNames' => ['QueueArn'],
        ]);

        return (string) ($attributes['Attributes']['QueueArn'] ?? '');
    }

    /**
     * Ajusta a política da fila para aceitar mensagens do tópico SNS.
     *
     * @param string $queueUrl URL da fila.
     * @param string $queueArn ARN da fila.
     * @param string $topicArn ARN do tópico.
     *
     * @return void
     */
    protected function allowSnsOnQueue(string $queueUrl, string $queueArn, string $topicArn): void
    {
        $policy = [
            'Version' => '2012-10-17',
            'Statement' => [
                [
                    'Sid' => 'Allow-SNS-SendMessage',
                    'Effect' => 'Allow',
                    'Principal' => ['AWS' => '*'],
                    'Action' => 'SQS:SendMessage',
                    'Resource' => $queueArn,
                    'Condition' => [
                        'ArnEquals' => [
                            'aws:SourceArn' => $topicArn,
                        ],
                    ],
                ],
            ],
        ];

        $this->sqsClient->setQueueAttributes([
            'QueueUrl' => $queueUrl,
            'Attributes' => [
                'Policy' => json_encode($policy, JSON_THROW_ON_ERROR),
            ],
        ]);
    }

    /**
     * Subscreve a fila ao tópico SNS.
     *
     * @param string $queueArn ARN da fila.
     * @param string $topicArn ARN do tópico.
     *
     * @return void
     */
    protected function subscribeQueueToTopic(string $queueArn, string $topicArn): void
    {
        $subscription = $this->snsClient->subscribe([
            'Protocol' => 'sqs',
            'TopicArn' => $topicArn,
            'Endpoint' => $queueArn,
            'ReturnSubscriptionArn' => true,
        ]);

        if (!empty($subscription['SubscriptionArn'])) {
            $this->snsClient->setSubscriptionAttributes([
                'SubscriptionArn' => $subscription['SubscriptionArn'],
                'AttributeName' => 'RawMessageDelivery',
                'AttributeValue' => 'true',
            ]);
        }
    }

    /**
     * Vincula a identidade SES ao tópico de bounce.
     *
     * @param string $identity Identidade SES.
     * @param string $topicArn ARN do tópico.
     *
     * @return void
     */
    protected function bindSesIdentity(string $identity, string $topicArn): void
    {
        $this->sesClient->setIdentityNotificationTopic([
            'Identity' => $identity,
            'NotificationType' => 'Bounce',
            'SnsTopic' => $topicArn,
        ]);

        $this->sesClient->setIdentityFeedbackForwardingEnabled([
            'Identity' => $identity,
            'ForwardingEnabled' => false,
        ]);
    }

    /**
     * Normaliza o nome a ser utilizado em recursos AWS.
     *
     * @param string $identity Domínio ou email original.
     *
     * @return string Identificador sanitizado.
     */
    protected function normalizeIdentity(string $identity): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '-', strtolower($identity));

        return trim((string) $slug, '-');
    }

    /**
     * Retorna o cliente SQS configurado.
     *
     * @return SqsClient
     */
    public function getSqsClient(): SqsClient
    {
        return $this->sqsClient;
    }

    /**
     * Calcula o nome da fila de bounces para a identidade informada.
     *
     * @param string $identity Domínio ou e-mail configurado.
     * @return string Nome da fila configurada.
     */
    public function getBounceQueueName(string $identity): string
    {
        $normalizedIdentity = $this->normalizeIdentity($identity);

        return getenv('aws.sqs.bounceQueue') ?: 'mailer-bounce-' . $normalizedIdentity;
    }
}
