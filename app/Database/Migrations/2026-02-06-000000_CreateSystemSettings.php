<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 1: Criar tabela system_settings
 * 
 * Esta migration cria a tabela que armazena configurações do sistema,
 * incluindo a versão atual do banco de dados.
 */
class CreateSystemSettings extends CreateSystemSettings
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Verificar se tabela já existe
        if ($db->tableExists('system_settings')) {
            log_message('info', 'CreateSystemSettings: Tabela system_settings já existe, pulando criação');
            return;
        }
        
        // Criar tabela system_settings
        $forge = \Config\Database::forge();
        
        $forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'setting_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'unique' => true,
            ],
            'setting_value' => [
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
        $forge->addUniqueKey('setting_key');
        $forge->createTable('system_settings', true);
        
        // Inserir versão inicial
        $db->table('system_settings')->insert([
            'setting_key' => 'db_version',
            'setting_value' => '1',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        log_message('info', 'CreateSystemSettings: Tabela system_settings criada com sucesso');
    }
    
    public function down()
    {
        $forge = \Config\Database::forge();
        $forge->dropTable('system_settings', true);
        
        log_message('info', 'CreateSystemSettings: Tabela system_settings removida');
    }
}
