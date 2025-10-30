<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Email\QueueManager;
use CodeIgniter\CLI\CLI;

/**
 * Controlador CLI responsável por processar a fila de envio.
 */
class QueueController extends BaseController
{
    /**
     * Processa a fila de envios pendentes.
     */
    public function process(): void
    {
        $batchSize = (int) ($this->request->getGet('batch') ?? 100);
        $queue = new QueueManager();
        $result = $queue->processQueue($batchSize);

        CLI::write('Processamento finalizado:' . PHP_EOL);
        CLI::write('Total processado: ' . ($result['processed'] ?? 0) . PHP_EOL);
        CLI::write('Enviados: ' . ($result['sent'] ?? 0) . PHP_EOL);
        CLI::write('Falhas: ' . ($result['failed'] ?? 0) . PHP_EOL);

        if (!empty($result['errors'])) {
            CLI::write('Erros:' . PHP_EOL);
            foreach ($result['errors'] as $error) {
                CLI::write('- ' . $error . PHP_EOL);
            }
        }
    }
}
