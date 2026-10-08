<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompanyTenancy extends Migration
{
    private array $tenantTables = [
        'users',
        'contacts',
        'contact_lists',
        'contact_list_members',
        'campaigns',
        'senders',
        'link_domains',
        'templates',
        'messages',
        'contact_imports',
        'receita_import_tasks',
        'system_settings',
    ];

    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->addKey('id', true);
        $this->forge->createTable('companies', true);

        $company = $this->db->table('companies')->where('name', 'Empresa legada')->get()->getRowArray();
        if ($company === null) {
            $this->db->table('companies')->insert([
                'name' => 'Empresa legada',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $legacyCompanyId = (int) $this->db->insertID();
        } else {
            $legacyCompanyId = (int) $company['id'];
        }

        foreach ($this->tenantTables as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            if (! $this->db->fieldExists('company_id', $table)) {
                $this->forge->addColumn($table, [
                    'company_id' => [
                        'type' => 'INT',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => true,
                        'after' => 'id',
                    ],
                ]);
            }

            $this->db->table($table)
                ->where('company_id', null)
                ->update(['company_id' => $legacyCompanyId]);

            $this->forge->modifyColumn($table, [
                'company_id' => [
                    'type' => 'INT',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                ],
            ]);

            if (! $this->indexExists($table, 'company_id')) {
                $this->forge->addKey('company_id');
            }

            $foreignKey = 'fk_' . $table . '_company';
            if (! $this->foreignKeyExists($table, $foreignKey)) {
                $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE', $foreignKey);
            }
        }

        $this->replaceTenantUniqueKey('contacts', 'email', 'uq_company_contact_email');
        $this->replaceTenantUniqueKey('senders', 'email', 'uq_company_sender_email');
        $this->replaceTenantUniqueKey('link_domains', 'domain', 'uq_company_link_domain');
        $this->replaceTenantUniqueKey('system_settings', 'setting_key', 'uq_company_setting');
    }

    public function down(): void
    {
        foreach (array_reverse($this->tenantTables) as $table) {
            if (! $this->db->tableExists($table) || ! $this->db->fieldExists('company_id', $table)) {
                continue;
            }

            $foreignKey = 'fk_' . $table . '_company';
            if ($this->foreignKeyExists($table, $foreignKey)) {
                $this->db->query('ALTER TABLE ' . $this->db->escapeIdentifier($table) . ' DROP FOREIGN KEY ' . $this->db->escapeIdentifier($foreignKey));
            }
            $this->forge->dropColumn($table, 'company_id');
        }

        $this->forge->dropTable('companies', true);
    }

    private function replaceTenantUniqueKey(string $table, string $column, string $newKey): void
    {
        if (! $this->db->tableExists($table)) {
            return;
        }

        $indexes = $this->db->query('SHOW INDEX FROM ' . $table)->getResultArray();
        $singleColumnUniqueKeys = [];

        foreach ($indexes as $index) {
            if ((int) $index['Non_unique'] === 0 && $index['Key_name'] !== 'PRIMARY' && (int) $index['Seq_in_index'] === 1 && $index['Column_name'] === $column) {
                $singleColumnUniqueKeys[$index['Key_name']] = true;
            }
        }

        foreach (array_keys($singleColumnUniqueKeys) as $keyName) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $keyName)) {
                $this->db->query('ALTER TABLE ' . $table . ' DROP INDEX `' . $keyName . '`');
            }
        }

        if (! $this->indexExists($table, $newKey)) {
            $this->db->query('ALTER TABLE ' . $table . ' ADD UNIQUE KEY ' . $newKey . ' (company_id, ' . $column . ')');
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return $this->db->query('SHOW INDEX FROM ' . $table . ' WHERE Key_name = ?', [$index])->getNumRows() > 0;
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $query = $this->db->query(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $foreignKey, 'FOREIGN KEY']
        );

        return $query->getNumRows() > 0;
    }
}