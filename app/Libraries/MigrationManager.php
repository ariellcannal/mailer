<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Migration Manager
 * 
 * Gerencia migrations automáticas do banco de dados
 */
class MigrationManager
{
    protected BaseConnection $db;
    protected string $migrationsPath;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->migrationsPath = APPPATH . 'Database/Migrations/';
    }
    
    /**
     * Verifica e executa migrations pendentes
     * 
     * @return array ['updated' => bool, 'from_version' => int, 'to_version' => int]
     */
    public function checkAndRunMigrations(): array
    {
        $currentVersion = $this->getCurrentVersion();
        $latestVersion = $this->getLatestVersion();
        
        // Debug verbose
        error_log("[MigrationManager] Current version: {$currentVersion}");
        error_log("[MigrationManager] Latest version: {$latestVersion}");
        log_message('debug', "MigrationManager: Current version = {$currentVersion}, Latest version = {$latestVersion}");
        
        if ($currentVersion >= $latestVersion) {
            return [
                'updated' => false,
                'from_version' => $currentVersion,
                'to_version' => $currentVersion,
            ];
        }
        
        // Executar migrations pendentes
        for ($version = $currentVersion + 1; $version <= $latestVersion; $version++) {
            error_log("[MigrationManager] Running migration {$version}");
            log_message('info', "MigrationManager: Running migration {$version}");
            try {
                $this->runMigration($version);
                $this->setVersion($version);
                error_log("[MigrationManager] Migration {$version} completed successfully");
                log_message('info', "MigrationManager: Migration {$version} completed");
            } catch (\Exception $e) {
                error_log("[MigrationManager] Migration {$version} failed: " . $e->getMessage());
                log_message('error', "MigrationManager: Migration {$version} failed: " . $e->getMessage());
                throw $e;
            }
        }
        
        return [
            'updated' => true,
            'from_version' => $currentVersion,
            'to_version' => $latestVersion,
        ];
    }
    
    /**
     * Obtém a versão atual do banco de dados
     * 
     * @return int
     */
    protected function getCurrentVersion(): int
    {
        // Verifica se tabela system_settings existe
        if (!$this->db->tableExists('system_settings')) {
            // Se não existir, assumir versão 0 (nenhuma migration executada ainda)
            return 0;
        }
        
        $result = $this->db->table('system_settings')
            ->where('setting_key', 'db_version')
            ->get()
            ->getRowArray();
        
        if (!$result) {
            // Se não existe registro, assumir versão 0
            return 0;
        }
        
        return (int) $result['setting_value'];
    }
    
    /**
     * Obtém a versão mais recente disponível nas migrations
     * 
     * @return int
     */
    protected function getLatestVersion(): int
    {
        if (!is_dir($this->migrationsPath)) {
            return 1;
        }
        
        $files = scandir($this->migrationsPath);
        $versions = [];
        
        foreach ($files as $file) {
            if (preg_match('/^Migration_(\d+)\.php$/', $file, $matches)) {
                $versions[] = (int) $matches[1];
            }
        }
        
        return empty($versions) ? 1 : max($versions);
    }
    
    /**
     * Executa uma migration específica
     * 
     * @param int $version
     * @return void
     */
    protected function runMigration(int $version): void
    {
        $className = "App\\Database\\Migrations\\Migration_{$version}";
        $file = $this->migrationsPath . "Migration_{$version}.php";
        
        if (!file_exists($file)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }
        
        require_once $file;
        
        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class not found: {$className}");
        }
        
        $migration = new $className();
        $migration->up();
    }
    

    
    /**
     * Define a versão do banco de dados
     * 
     * @param int $version
     * @return void
     */
    protected function setVersion(int $version): void
    {
        // Verificar se a tabela existe antes de tentar inserir/atualizar
        if (!$this->db->tableExists('system_settings')) {
            // Tabela ainda não foi criada, aguardar Migration_1
            return;
        }
        
        $existing = $this->db->table('system_settings')
            ->where('setting_key', 'db_version')
            ->get()
            ->getRowArray();
        
        if ($existing) {
            $this->db->table('system_settings')
                ->where('setting_key', 'db_version')
                ->update([
                    'setting_value' => (string) $version,
                ]);
        } else {
            $this->db->table('system_settings')->insert([
                'setting_key' => 'db_version',
                'setting_value' => (string) $version,
            ]);
        }
    }
}
