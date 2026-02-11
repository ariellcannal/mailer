<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adiciona a coluna delivery_at na tabela message_sends
 */
class AddDeliveryAtColumnToMessageSendsTable extends Migration
{

    public function up()
    {
        // Adicionar campos de bytes
        $fields = [
            'delivery_at' => [
                'type' => 'DATETIME',
                'default' => null,
                'comment' => '',
                'after' => 'send_at'
            ]
        ];

        $this->forge->addColumn('message_sends', $fields);

        log_message('info', 'Campo delivery_at adicionados à tabela message_sends');
    }

    public function down()
    {
        $this->forge->dropColumn('message_sends', [
            'delivered'
        ]);

        log_message('info', 'Campo delivery_at removido tabela message_sends');
    }
}
