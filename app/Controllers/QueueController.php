<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Email\QueueManager;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controlador responsável por processar a fila de envio.
 */
class QueueController extends BaseController
{
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
}
