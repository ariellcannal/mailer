<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adiciona campo bounce_subtype às tabelas message_sends e contacts
 * Para armazenar subtipo detalhado do bounce (ex: General, NoEmail, MailboxFull)
 */
class AddBounceSubtypeFields extends Migration
{
    public function up(): void
    {
        // Adicionar bounce_subtype em message_sends
        $this->forge->addColumn('message_sends', [
            'bounce_subtype' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'bounce_type',
                'comment' => 'Subtipo do bounce (ex: General, NoEmail, MailboxFull)'
            ]
        ]);

        // Adicionar bounce_subtype em contacts
        $this->forge->addColumn('contacts', [
            'bounce_subtype' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'bounce_type',
                'comment' => 'Subtipo do último bounce recebido'
            ]
        ]);

        log_message('info', 'Campo bounce_subtype adicionado às tabelas message_sends e contacts');
    }

    public function down(): void
    {
        // Remover colunas
        $this->forge->dropColumn('message_sends', 'bounce_subtype');
        $this->forge->dropColumn('contacts', 'bounce_subtype');

        log_message('info', 'Campo bounce_subtype removido das tabelas message_sends e contacts');
    }
}
