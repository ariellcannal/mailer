<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;

/**
 * Migration 4: Adicionar campos total_bytes e processed_bytes na tabela receita_import_tasks
 */
class Migration_4
{
    protected BaseConnection $db;
    
    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }
    
    public function up(): void
    {
        $forge = \Config\Database::forge();
        
        // Adicionar campos de bytes
        $fields = [
            'total_bytes' => [
                'type' => 'BIGINT',
                'default' => 0,
                'comment' => 'Total de bytes a processar',
                'after' => 'total_files',
            ],
            'processed_bytes' => [
                'type' => 'BIGINT',
                'default' => 0,
                'comment' => 'Bytes já processados',
                'after' => 'processed_files',
            ],
        ];
        
        $forge->addColumn('receita_import_tasks', $fields);
        
        log_message('info', 'Migration 4: Campos total_bytes e processed_bytes adicionados à tabela receita_import_tasks');
    }

    public function down(): void
    {
        $forge = \Config\Database::forge();
        
        $forge->dropColumn('receita_import_tasks', ['total_bytes', 'processed_bytes']);
        
        log_message('info', 'Migration 4: Campos total_bytes e processed_bytes removidos da tabela receita_import_tasks');
    }
}
