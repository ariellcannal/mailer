<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Adicionar coluna nickname_column à tabela contact_imports
 */
class AddNicknameColumnToContactImports extends Migration
{
    public function up(): void
    {
        $forge = \Config\Database::forge();
        
        $fields = [
            'nickname_column' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'name_column',
            ],
        ];
        
        $forge->addColumn('contact_imports', $fields);
    }
    
    public function down(): void
    {
        $forge = \Config\Database::forge();
        $forge->dropColumn('contact_imports', 'nickname_column');
    }
}
