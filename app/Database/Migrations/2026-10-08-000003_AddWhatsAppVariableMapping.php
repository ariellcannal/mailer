<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWhatsAppVariableMapping extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('variable_mapping', 'whatsapp_messages')) {
            $this->forge->addColumn('whatsapp_messages', [
                'variable_mapping' => [
                    'type' => 'JSON',
                    'null' => true,
                    'after' => 'list_ids',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('variable_mapping', 'whatsapp_messages')) {
            $this->forge->dropColumn('whatsapp_messages', 'variable_mapping');
        }
    }
}