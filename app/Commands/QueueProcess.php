<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Email\QueueManager;

# * * * * * /usr/local/bin/ea-php82 /home/cannal/public_html/mailer/spark queue:process 100 >> /dev/null 2>&1
# php C:\TI\projetos\cannal\public_html\mailer\spark queue:process 100
class QueueProcess extends BaseCommand
{

    protected $group = 'Mailer';

    protected $name = 'queue:process';

    protected $description = 'Processa a fila de envios pendentes com proteção contra execução duplicada.';

    protected $lockFile;

    public function run(array $params)
    {
        // 0. Reduzir prioridade do processo
        if (function_exists('proc_nice')) {
            proc_nice(10);
        }
        
        // 1. Configurações de Limite
        set_time_limit(60); // Limite de 1 minuto
        ini_set('memory_limit', '128M'); // Limite de memória seguro para CLI
        
        
        $this->lockFile = WRITEPATH . 'queue_process.lock';

        // 2. Mecanismo de Lock com validação de PID
        if ($this->isLocked()) {
            CLI::write("O processo já está em execução. Saindo...", 'yellow');
            return;
        }

        $this->createLock();
        try {
            
            $batchSize = (int) ($params[0] ?? 100);
            CLI::write("Iniciando processamento da fila (Lote: $batchSize)...", 'yellow');
            
            // Otimização de Memória: Desativa log de queries no banco
            $db = \Config\Database::connect();
            if (property_exists($db, 'saveQueries')) {
                $db->saveQueries = false;
            }

            $queue = new QueueManager();
            $result = $queue->processQueue($batchSize);

            $this->displayOutput($result);

            // Força coleta de lixo ao final do lote
            gc_collect_cycles();
        } catch (\Throwable $e) {
            CLI::error('Erro durante o processamento: ' . $e->getMessage());
        } finally {
            $this->removeLock();
        }
    }

    private function isLocked(): bool
    {
        if (! file_exists($this->lockFile))
            return false;

        $pid = (int) file_get_contents($this->lockFile);

        // Verifica se o PID ainda existe no sistema (Linux/Unix)
        if (function_exists('posix_getpgid')) {
            if (posix_getpgid($pid) === false) {
                CLI::write("Detectado lock órfão (PID $pid inexistente). Reiniciando...", 'cyan');
                return false;
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            // Verificação alternativa para Windows
            $output = shell_exec("tasklist /FI \"PID eq $pid\" /NH");
            if (strpos($output, (string) $pid) === false)
                return false;
        }

        return true;
    }

    private function createLock()
    {
        file_put_contents($this->lockFile, getmypid());
    }

    private function removeLock()
    {
        if (file_exists($this->lockFile))
            unlink($this->lockFile);
    }

    private function displayOutput(array $result): void
    {
        CLI::write('Total processado: ' . ($result['processed'] ?? 0));
        CLI::write('Enviados: ' . ($result['sent'] ?? 0), 'green');
        CLI::write('Falhas: ' . ($result['failed'] ?? 0), 'red');
        CLI::write('Ignorados: ' . ($result['skipped'] ?? 0), 'yellow');

        if (! empty($result['errors'])) {
            CLI::newLine();
            CLI::error('Erros encontrados:');
            foreach ($result['errors'] as $error) {
                CLI::error('- ' . $error);
            }
        }
    }
}