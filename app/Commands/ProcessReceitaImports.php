<?php

namespace App\Commands;

use App\Libraries\ReceitaAsyncProcessor;
use App\Models\ReceitaImportTaskModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando para processar fila de importações da Receita Federal
 * 
 * Uso: php spark receita:process
 * CRON: * * * * * cd /path/to/project && php spark receita:process >> /dev/null 2>&1
 */
class ProcessReceitaImports extends BaseCommand
{
    protected $group = 'Receita';
    protected $name = 'receita:process';
    protected $description = 'Processa fila de importações da Receita Federal (executar via CRON a cada minuto)';
    
    private $lockFile;

    public function run(array $params)
    {
        // ========================================
        // OTIMIZAÇÕES DE RECURSOS PARA t3a.small
        // ========================================
        
        // 1. Reduzir prioridade do processo (nice 10 = baixa prioridade)
        if (function_exists('proc_nice')) {
            proc_nice(10);
        }
        
        // 2. Limites de recursos
        set_time_limit(90);
        ini_set('memory_limit', '256M'); // Aumentado para suportar arquivos grandes
        gc_enable();
        
        // 3. Desativar logs de queries
        $db = \Config\Database::connect();
        if (property_exists($db, 'saveQueries')) {
            $db->saveQueries = false;
        }
        
        // 4. Lockfile para evitar execuções concorrentes
        $this->lockFile = WRITEPATH . 'receita_process.lock';
        
        if ($this->isLocked()) {
            CLI::write('[' . date('Y-m-d H:i:s') . '] Processo já em execução. Saindo...', 'yellow');
            return;
        }
        
        $this->createLock();

        try {
            CLI::write('[' . date('Y-m-d H:i:s') . '] Iniciando processamento de importações da Receita...', 'green');
            
            // Log de uso de memória inicial
            $memStart = memory_get_usage(true);
            CLI::write('Memória inicial: ' . $this->formatBytes($memStart), 'cyan');

            $processor = new ReceitaAsyncProcessor();
            
            // Buscar próxima tarefa agendada
            $taskModel = new ReceitaImportTaskModel();
            $task = $taskModel->getNextScheduledTask();
            
            if (!$task) {
                CLI::write('Nenhuma tarefa agendada encontrada.', 'yellow');
                return;
            }
            
            CLI::write("Processando tarefa #{$task['id']}: {$task['name']}", 'cyan');
            
            // Processar tarefa com limite de tempo (55 segundos)
            $result = $processor->processTaskById((int) $task['id'], 55);
            
            if (!$result['success']) {
                CLI::error('Erro: ' . $result['message']);
                return;
            }
            
            if ($result['completed']) {
                CLI::write("Tarefa #{$task['id']} concluída com sucesso!", 'green');
                CLI::write("Total processado: {$result['processed_lines']} linhas", 'green');
                CLI::write("Total importado: {$result['imported_lines']} linhas", 'green');
            } else {
                CLI::write("Processamento parcial da tarefa #{$task['id']}", 'yellow');
                CLI::write("Progresso: {$result['processed_files']}/{$result['total_files']} arquivos", 'yellow');
                CLI::write("Linhas processadas: {$result['processed_lines']}", 'yellow');
            }
            
            // Log de uso de memória final
            $memEnd = memory_get_usage(true);
            $memPeak = memory_get_peak_usage(true);
            CLI::write('Memória final: ' . $this->formatBytes($memEnd), 'cyan');
            CLI::write('Pico de memória: ' . $this->formatBytes($memPeak), 'cyan');
            CLI::write('Memória liberada: ' . $this->formatBytes($memStart - $memEnd), 'cyan');
            
        } catch (\Exception $e) {
            CLI::error('Erro ao processar importação: ' . $e->getMessage());
            CLI::write($e->getTraceAsString(), 'red');
            log_message('error', 'ProcessReceitaImports error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        } finally {
            $this->removeLock();
            // Forçar coleta de lixo ao final
            gc_collect_cycles();
        }
        
        CLI::write('[' . date('Y-m-d H:i:s') . '] Processamento finalizado.', 'green');
    }
    
    /**
     * Verifica se há um lock ativo
     */
    private function isLocked(): bool
    {
        if (!file_exists($this->lockFile)) {
            return false;
        }
        
        $lockData = json_decode(file_get_contents($this->lockFile), true);
        $pid = $lockData['pid'] ?? 0;
        
        // Verificar se o PID ainda existe
        if (function_exists('posix_getpgid')) {
            if (posix_getpgid($pid) === false) {
                CLI::write("Detectado lock órfão (PID $pid inexistente). Removendo...", 'cyan');
                unlink($this->lockFile);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Cria lockfile
     */
    private function createLock(): void
    {
        $lockData = [
            'pid' => getmypid(),
            'started_at' => date('Y-m-d H:i:s'),
            'command' => 'receita:process'
        ];
        file_put_contents($this->lockFile, json_encode($lockData));
    }
    
    /**
     * Remove lockfile
     */
    private function removeLock(): void
    {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }
    
    /**
     * Formata bytes para leitura humana
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
