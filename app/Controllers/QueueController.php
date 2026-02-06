<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Email\QueueManager;
use App\Libraries\Email\BounceProcessor;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controlador responsável por processar a fila de envio.
 */
class QueueController extends BaseController
{
    /**
     *  CRON SETUP
     *  
     *  LINUX
        * * * * * /usr/local/bin/ea-php82 /home/cannal/public_html/mailer/public/index.php queue/process >> /dev/null 2>&1 
     */

    /**
     * Processa a fila de envios pendentes, permitindo execução via CLI ou navegador em desenvolvimento.
     *
     * @return ResponseInterface Resposta com o resumo do processamento.
     */
    public function process(): ResponseInterface
    {
        if (! is_cli() && ENVIRONMENT !== 'development') {
            throw PageNotFoundException::forPageNotFound();
        }

        $batchSize = (int) ($this->request->getGet('batch') ?? 100);
        $queue = new QueueManager();
        $result = $queue->processQueue($batchSize);

        $output = $this->buildOutput($result);

        if (is_cli()) {
            CLI::write($output . PHP_EOL);

            return $this->response->setBody('');
        }

        return $this->response
            ->setContentType('text/plain')
            ->setBody($output . PHP_EOL);
    }

    /**
     * Processa notificações de bounces e complaints no SNS/SQS.
     * 
     * CRON SETUP (OBRIGATÓRIO PARA EVITAR BLACKLIST)
     * 
     * LINUX:
     * */5 * * * * /usr/local/bin/ea-php82 /home/cannal/public_html/mailer/public/index.php queue/process-bounces >> /home/cannal/public_html/mailer/writable/logs/bounces.log 2>&1
     * 
     * OU via wget/curl:
     * */5 * * * * wget -q -O- https://seu-dominio.com/queue/process-bounces >> /home/cannal/public_html/mailer/writable/logs/bounces.log 2>&1
     * 
     * IMPORTANTE:
     * - Executar a cada 5 minutos para processar bounces rapidamente
     * - Bounces não processados podem causar suspensão da conta AWS SES
     * - Complaints (spam reports) são automaticamente convertidos em opt-outs
     *
     * @return ResponseInterface
     */
    public function processBounces(): ResponseInterface
    {
        if (! is_cli() && ENVIRONMENT !== 'development') {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            $processor = new BounceProcessor();
            $result = $processor->process();
        } catch (\Throwable $exception) {
            $result = [
                'processed' => 0,
                'bounced' => 0,
                'complained' => 0,
                'errors' => ['Falha ao processar bounces: ' . $exception->getMessage()],
            ];
        }

        $output = $this->buildBounceOutput($result);

        if (is_cli()) {
            CLI::write($output . PHP_EOL);

            return $this->response->setBody('');
        }

        return $this->response
            ->setContentType('text/plain')
            ->setBody($output . PHP_EOL);
    }

    /**
     * Monta o resumo do processamento da fila.
     *
     * @param array<string, mixed> $result Resultado do processamento.
     *
     * @return string Texto com os totais e eventuais erros.
     */
    private function buildOutput(array $result): string
    {
        $lines = [
            'Processamento finalizado:',
            '',
            'Total processado: ' . ($result['processed'] ?? 0),
            'Enviados: ' . ($result['sent'] ?? 0),
            'Falhas: ' . ($result['failed'] ?? 0),
            'Ignorados: ' . ($result['skipped'] ?? 0),
        ];

        if (!empty($result['errors'])) {
            $lines[] = '';
            $lines[] = 'Erros:';
            foreach ($result['errors'] as $error) {
                $lines[] = '- ' . $error;
            }
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Monta resumo textual do processamento de bounces.
     *
     * @param array<string, mixed> $result Resultado do processamento.
     * @return string
     */
    private function buildBounceOutput(array $result): string
    {
        $lines = [
            'Bounces processados:',
            '',
            'Mensagens analisadas: ' . ($result['processed'] ?? 0),
            'Bounces registrados: ' . ($result['bounced'] ?? 0),
            'Complaints registradas: ' . ($result['complained'] ?? 0),
        ];

        if (!empty($result['errors'])) {
            $lines[] = '';
            $lines[] = 'Erros:';
            foreach ($result['errors'] as $error) {
                $lines[] = '- ' . $error;
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
