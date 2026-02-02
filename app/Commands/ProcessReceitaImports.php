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

    public function run(array $params)
    {
        // Otimizações de memória e CPU
        set_time_limit(90);
        ini_set('memory_limit', '128M');
        gc_enable();
        
        $db = \Config\Database::connect();
        if (property_exists($db, 'saveQueries')) {
            $db->saveQueries = false;
        }

        CLI::write('[' . date('Y-m-d H:i:s') . '] Iniciando processamento de importações da Receita...', 'green');

        try {
            $processor = new ReceitaAsyncProcessor();
            
            // Verificar se já existe processo em execução
            if ($processor->isLocked()) {
                CLI::write('Outro processo já está em execução. Aguardando...', 'yellow');
                return;
            }
            
            // Buscar próxima tarefa agendada
            $taskModel = new ReceitaImportTaskModel();
            $task = $taskModel->getNextScheduledTask();
            
            if (!$task) {
                CLI::write('Nenhuma tarefa agendada encontrada.', 'yellow');
                return;
            }
            
            CLI::write("Processando tarefa #{$task['id']}: {$task['name']}", 'cyan');
            
            // Processar tarefa com limite de tempo (55 segundos)
            $result = $processor->processTask($task['id'], 55);
            
            if ($result['completed']) {
                CLI::write("Tarefa #{$task['id']} concluída com sucesso!", 'green');
                CLI::write("Total processado: {$result['processed_lines']} linhas", 'green');
                CLI::write("Total importado: {$result['imported_lines']} linhas", 'green');
            } else {
                CLI::write("Processamento parcial da tarefa #{$task['id']}", 'yellow');
                CLI::write("Progresso: {$result['processed_files']}/{$result['total_files']} arquivos", 'yellow');
                CLI::write("Linhas processadas: {$result['processed_lines']}", 'yellow');
            }
            
        } catch (\Exception $e) {
            CLI::error('Erro ao processar importação: ' . $e->getMessage());
            CLI::write($e->getTraceAsString(), 'red');
            log_message('error', 'ProcessReceitaImports error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        }
        
        CLI::write('[' . date('Y-m-d H:i:s') . '] Processamento finalizado.', 'green');
    }
}
