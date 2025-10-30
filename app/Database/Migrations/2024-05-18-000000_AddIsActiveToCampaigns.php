<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Classe responsável por incluir o campo de ativação em campanhas.
 */
class AddIsActiveToCampaigns extends Migration
{
    /**
     * Executa a adição do campo is_active na tabela de campanhas.
     */
    public function up(): void
    {
        $fields = [
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'after'      => 'total_optouts',
            ],
        ];

        $this->forge->addColumn('campaigns', $fields);
    }

    /**
     * Reverte a adição do campo is_active na tabela de campanhas.
     */
    public function down(): void
    {
        if ($this->db->fieldExists('is_active', 'campaigns')) {
            $this->forge->dropColumn('campaigns', 'is_active');
        }
    }
}
