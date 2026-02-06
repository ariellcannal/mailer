<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 4: Adicionar campos total_bytes e processed_bytes na tabela receita_import_tasks
 */
class AddBytesCols extends Migration
{
    public function up(): void
    {
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
        
        $this->forge->addColumn('receita_import_tasks', $fields);
        
        log_message('info', 'AddBytesCols: Campos total_bytes e processed_bytes adicionados à tabela receita_import_tasks');
    }

    public function down(): void
    {
        $this->forge->dropColumn('receita_import_tasks', ['total_bytes', 'processed_bytes']);
        
        log_message('info', 'AddBytesCols: Campos total_bytes e processed_bytes removidos da tabela receita_import_tasks');
    }
}
