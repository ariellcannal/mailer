<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando para executar migrations customizadas via MigrationManager
 */
class RunCustomMigrations extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'migrate:custom';
    protected $description = 'Executa migrations customizadas via MigrationManager';

    public function run(array $params)
    {
        CLI::write('=== Executando Migrations Customizadas ===', 'yellow');
        CLI::newLine();

        try {
            $migrationManager = new \App\Libraries\MigrationManager();
            
            CLI::write('MigrationManager instanciado com sucesso!', 'green');
            CLI::newLine();
            
            $result = $migrationManager->checkAndRunMigrations();
            
            CLI::write('=== Resultado ===', 'yellow');
            CLI::write('Atualizado: ' . ($result['updated'] ? 'SIM' : 'NÃO'));
            CLI::write('Versão anterior: ' . $result['from_version']);
            CLI::write('Versão atual: ' . $result['to_version']);
            CLI::newLine();
            
            if ($result['updated']) {
                CLI::write('✅ Migrations executadas com sucesso!', 'green');
            } else {
                CLI::write('ℹ️  Banco de dados já está atualizado!', 'blue');
            }
            
            // Verificar tabelas criadas
            $db = \Config\Database::connect();
            CLI::newLine();
            CLI::write('=== Tabelas da Receita Federal ===', 'yellow');
            $tables = ['receita_empresas', 'receita_estabelecimentos', 'receita_socios', 'receita_import_tasks'];
            foreach ($tables as $table) {
                $exists = $db->tableExists($table);
                CLI::write('- ' . $table . ': ' . ($exists ? '✅' : '❌'));
            }
            
        } catch (\Exception $e) {
            CLI::error('ERRO: ' . $e->getMessage());
            CLI::write($e->getTraceAsString(), 'red');
            return EXIT_ERROR;
        }

        return EXIT_SUCCESS;
    }
}
