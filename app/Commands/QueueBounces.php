<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Email\BounceProcessor;

#*/10 * * * * /usr/local/bin/ea-php82 /home/cannal/public_html/mailer/spark queue:bounces >> /dev/null 2>&1
class QueueBounces extends BaseCommand
{

    protected $group = 'Mailer';

    protected $name = 'queue:bounces';

    protected $description = 'Processa notificações de bounces e complaints no SNS/SQS.';
    
    private $lockFile;

    public function run(array $params)
    {
        // Reduzir prioridade do processo
        if (function_exists('proc_nice')) {
            proc_nice(10);
        }
        
        // Otimizações de memória e CPU
        set_time_limit(60);
        ini_set('memory_limit', '64M');
        gc_enable();
        
        // Lockfile
        $this->lockFile = WRITEPATH . 'bounce_process.lock';
        
        if ($this->isLocked()) {
            CLI::write('Processo já em execução. Saindo...', 'yellow');
            return;
        }
        
        $this->createLock();
        
        CLI::write("Verificando bounces e complaints...", 'yellow');

        try {
            $processor = new BounceProcessor();
            $result = $processor->process();
        } catch (\Throwable $exception) {
            CLI::error('Falha crítica: ' . $exception->getMessage());
            return;
        } finally {
            $this->removeLock();
            gc_collect_cycles();
        }

        $this->displayBounceOutput($result);
    }
    
    private function isLocked(): bool
    {
        if (!file_exists($this->lockFile)) {
            return false;
        }
        
        $lockData = json_decode(file_get_contents($this->lockFile), true);
        $pid = $lockData['pid'] ?? 0;
        
        if (function_exists('posix_getpgid')) {
            if (posix_getpgid($pid) === false) {
                CLI::write("Detectado lock órfão (PID $pid inexistente). Removendo...", 'cyan');
                unlink($this->lockFile);
                return false;
            }
        }
        
        return true;
    }
    
    private function createLock(): void
    {
        $lockData = [
            'pid' => getmypid(),
            'started_at' => date('Y-m-d H:i:s'),
            'command' => 'queue:bounces'
        ];
        file_put_contents($this->lockFile, json_encode($lockData));
    }
    
    private function removeLock(): void
    {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }

    private function displayBounceOutput(array $result): void
    {
        CLI::write('Mensagens analisadas: ' . ($result['processed'] ?? 0));
        CLI::write('Registro de Entrega: ' . ($result['deliveries'] ?? 0), 'green');
        CLI::write('Registro de Bounce: ' . ($result['bounces'] ?? 0), 'red');
        CLI::write('Registro de Complaint: ' . ($result['complaints'] ?? 0), 'red');

        if (! empty($result['errors'])) {
            CLI::newLine();
            CLI::error('Detalhes dos erros:');
            foreach ($result['errors'] as $error) {
                CLI::error('- ' . $error);
            }
        }
    }
}