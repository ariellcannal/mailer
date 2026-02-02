<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;

/**
 * Migration 3: Criar tabela receita_import_tasks para processamento assíncrono
 */
class Migration_3
{
    protected BaseConnection $db;
    
    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }
    
    public function up(): void
    {
        $forge = \Config\Database::forge();
        
        // Criar tabela receita_import_tasks
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
                'null' => true,
                'comment' => 'Nome opcional da tarefa',
            ],
            'cnaes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'CNAEs filtrados (JSON array)',
            ],
            'ufs' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Estados filtrados (JSON array)',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['agendada', 'em_andamento', 'concluida', 'erro'],
                'default' => 'agendada',
                'comment' => 'Status da tarefa',
            ],
            'total_files' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Total de arquivos a processar',
            ],
            'processed_files' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Arquivos já processados',
            ],
            'current_file' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Arquivo sendo processado atualmente',
            ],
            'total_lines' => [
                'type' => 'BIGINT',
                'default' => 0,
                'comment' => 'Total estimado de linhas',
            ],
            'processed_lines' => [
                'type' => 'BIGINT',
                'default' => 0,
                'comment' => 'Linhas já processadas',
            ],
            'imported_lines' => [
                'type' => 'BIGINT',
                'default' => 0,
                'comment' => 'Linhas efetivamente importadas',
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Mensagem de erro se houver',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Quando o processamento iniciou',
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Quando o processamento terminou',
            ],
        ]);

        $forge->addKey('id', true);
        $forge->addKey('status');
        $forge->addKey('created_at');
        
        $forge->createTable('receita_import_tasks');
        
        log_message('info', 'Migration 3: Tabela receita_import_tasks criada com sucesso');
    }

    public function down(): void
    {
        $forge = \Config\Database::forge();
        $forge->dropTable('receita_import_tasks', true);
        log_message('info', 'Migration 3: Tabela receita_import_tasks removida');
    }
}
