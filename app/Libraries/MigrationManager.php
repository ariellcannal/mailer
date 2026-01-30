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
        
        if ($currentVersion >= $latestVersion) {
            return [
                'updated' => false,
                'from_version' => $currentVersion,
                'to_version' => $currentVersion,
            ];
        }
        
        // Executar migrations pendentes
        for ($version = $currentVersion + 1; $version <= $latestVersion; $version++) {
            $this->runMigration($version);
        }
        
        // Atualizar versão no banco
        $this->setVersion($latestVersion);
        
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
        // Verifica se tabela settings existe
        if (!$this->db->tableExists('settings')) {
            // Criar tabela settings
            $this->createSettingsTable();
            $this->setVersion(1);
            return 1;
        }
        
        $result = $this->db->table('settings')
            ->where('name', 'db_version')
            ->get()
            ->getRowArray();
        
        return $result ? (int) $result['value'] : 1;
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
        
        $migration = new $className($this->db);
        $migration->up();
    }
    
    /**
     * Cria a tabela settings
     * 
     * @return void
     */
    protected function createSettingsTable(): void
    {
        $forge = \Config\Database::forge();
        
        $forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $forge->addKey('id', true);
        $forge->addUniqueKey('name');
        $forge->createTable('settings', true);
    }
    
    /**
     * Define a versão do banco de dados
     * 
     * @param int $version
     * @return void
     */
    protected function setVersion(int $version): void
    {
        $existing = $this->db->table('settings')
            ->where('name', 'db_version')
            ->get()
            ->getRowArray();
        
        if ($existing) {
            $this->db->table('settings')
                ->where('name', 'db_version')
                ->update([
                    'value' => $version,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            $this->db->table('settings')->insert([
                'name' => 'db_version',
                'value' => $version,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
