<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adiciona armazenamento de progresso às mensagens.
 */
class AddProgressDataToMessages extends Migration
{
    /**
     * Cria a coluna de progresso no cadastro de mensagens.
     *
     * @return void
     */
    public function up(): void
    {
        $this->forge->addColumn('messages', [
            'progress_data' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'after' => 'total_optouts',
            ],
        ]);
    }

    /**
     * Remove a coluna de progresso no rollback.
     *
     * @return void
     */
    public function down(): void
    {
        $this->forge->dropColumn('messages', 'progress_data');
    }
}
