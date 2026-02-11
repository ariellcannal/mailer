<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBouncesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'message_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'ID da tabela messages (pai)',
            ],
            'contact_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'ID do contato que sofreu o bounce',
            ],
            'message_send_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'ID da tentativa específica na message_sends',
            ],
            'bounce_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'comment'    => 'Ex: permanent, transient, undetermined',
            ],
            'bounce_subtype' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'comment'    => 'Ex: General, NoEmail, MailboxFull',
            ],
            'reason' => [
                'type'       => 'TEXT',
                'null'       => true,
                'comment'    => 'Código de erro técnico (diagnosticCode)',
            ],
            'raw_payload' => [
                'type'       => 'JSON',
                'null'       => true,
                'comment'    => 'JSON completo da AWS para auditoria',
            ],
            'bounced_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
        ]);

        $this->forge->addKey('id', true); // Primary Key

        // Definição das Foreign Keys
        // Sintaxe: addForeignKey(coluna_local, tabela_pai, coluna_pai, onUpdate, onDelete)
        $this->forge->addForeignKey('message_id', 'messages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('contact_id', 'contacts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('message_send_id', 'message_sends', 'id', 'SET NULL', 'CASCADE');

        $attributes = ['ENGINE' => 'InnoDB'];
        $this->forge->createTable('bounces', false, $attributes);
    }

    public function down()
    {
        $this->forge->dropTable('bounces');
    }
}