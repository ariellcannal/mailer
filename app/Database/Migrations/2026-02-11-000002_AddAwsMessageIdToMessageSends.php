<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adiciona campo aws_message_id para armazenar o MessageId retornado pela AWS SES
 * Permite vincular webhooks de bounce/complaint/delivery com os registros do banco
 */
class AddAwsMessageIdToMessageSends extends Migration
{
    public function up(): void
    {
        // Adicionar coluna aws_message_id
        $this->forge->addColumn('message_sends', [
            'aws_message_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'tracking_hash',
                'comment' => 'MessageId retornado pela AWS SES no momento do envio'
            ]
        ]);

        // Criar índice para buscas rápidas por aws_message_id
        $this->db->query("
            CREATE INDEX IF NOT EXISTS idx_aws_message_id 
            ON message_sends (aws_message_id)
        ");

        log_message('info', 'Campo aws_message_id adicionado à tabela message_sends');
    }

    public function down(): void
    {
        // Remover índice
        $this->db->query("DROP INDEX IF EXISTS idx_aws_message_id ON message_sends");

        // Remover coluna
        $this->forge->dropColumn('message_sends', 'aws_message_id');

        log_message('info', 'Campo aws_message_id removido da tabela message_sends');
    }
}
