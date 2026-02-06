<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 2: Criar tabela contact_imports para importação assíncrona
 */
class Migration_2 extends Migration
{
    
    public function up(): void
    {
        $forge = \Config\Database::forge();
        
        // Criar tabela contact_imports
        $forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'filename' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'filepath' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'processing', 'completed', 'failed'],
                'default' => 'pending',
            ],
            'total_rows' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'processed_rows' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'imported_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'skipped_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'error_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'progress_percent' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.00,
            ],
            'email_column' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'name_column' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'list_ids' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of list IDs',
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'error_details' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'comment' => 'JSON array of detailed errors',
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
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
        $forge->addKey('status');
        $forge->addKey('created_at');
        $forge->createTable('contact_imports', true);
    }
    
    public function down(): void
    {
        $forge = \Config\Database::forge();
        $forge->dropTable('contact_imports', true);
    }
}
