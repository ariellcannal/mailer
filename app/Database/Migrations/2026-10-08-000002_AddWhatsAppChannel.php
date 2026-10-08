<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWhatsAppChannel extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('phone', 'contacts')) {
            $this->forge->addColumn('contacts', [
                'phone' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                    'after' => 'email',
                ],
            ]);
        }

        if (! $this->db->fieldExists('phone_column', 'contact_imports')) {
            $this->forge->addColumn('contact_imports', [
                'phone_column' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => 'email_column',
                ],
            ]);
        }

        $this->createNumbersTable();
        $this->createTemplatesTable();
        $this->createMessagesTable();
        $this->createSendsTable();
    }

    public function down(): void
    {
        $this->forge->dropTable('whatsapp_sends', true);
        $this->forge->dropTable('whatsapp_messages', true);
        $this->forge->dropTable('whatsapp_templates', true);
        $this->forge->dropTable('whatsapp_numbers', true);

        if ($this->db->fieldExists('phone', 'contacts')) {
            $this->forge->dropColumn('contacts', 'phone');
        }
        if ($this->db->fieldExists('phone_column', 'contact_imports')) {
            $this->forge->dropColumn('contact_imports', 'phone_column');
        }
    }

    private function createNumbersTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'waba_id' => ['type' => 'VARCHAR', 'constraint' => 255],
            'phone_number_id' => ['type' => 'VARCHAR', 'constraint' => 64],
            'display_phone_number' => ['type' => 'VARCHAR', 'constraint' => 32],
            'verified_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'quality_rating' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'CONNECTED'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'synced_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('phone_number_id', 'uq_wa_phone_number_id');
        $this->forge->addKey('company_id');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('whatsapp_numbers', true);
    }

    private function createTemplatesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 128],
            'language' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pt_BR'],
            'category' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'UTILITY'],
            'header_type' => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'NONE'],
            'header_content' => ['type' => 'VARCHAR', 'constraint' => 1024, 'null' => true],
            'body_content' => ['type' => 'LONGTEXT'],
            'footer_content' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'buttons' => ['type' => 'JSON', 'null' => true],
            'meta_template_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'draft'],
            'rejection_reason' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'name', 'language'], 'uq_wa_template_company');
        $this->forge->addKey('company_id');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('whatsapp_templates', true);
    }

    private function createMessagesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'campaign_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'template_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'whatsapp_number_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'list_ids' => ['type' => 'TEXT'],
            'scheduled_at' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'draft'],
            'total_recipients' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'total_sent' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'total_delivered' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'total_read' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'total_failed' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'status', 'scheduled_at'], false, false, 'idx_wa_queue');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('campaign_id', 'campaigns', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('template_id', 'whatsapp_templates', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('whatsapp_number_id', 'whatsapp_numbers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('whatsapp_messages', true);
    }

    private function createSendsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'whatsapp_message_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'contact_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'destination_phone' => ['type' => 'VARCHAR', 'constraint' => 32],
            'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pending'],
            'meta_message_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'error_code' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'sent_at' => ['type' => 'DATETIME', 'null' => true],
            'delivered_at' => ['type' => 'DATETIME', 'null' => true],
            'read_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['whatsapp_message_id', 'contact_id'], 'uq_wa_send_recipient');
        $this->forge->addKey('meta_message_id');
        $this->forge->addKey(['company_id', 'status'], false, false, 'idx_wa_send_status');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('whatsapp_message_id', 'whatsapp_messages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('contact_id', 'contacts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('whatsapp_sends', true);
    }
}