<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;

/**
 * Migration 5: Adicionar campo situacoes_fiscais e criar foreign keys + índices otimizados
 */
class Migration_5
{
    protected BaseConnection $db;
    
    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }
    
    public function up(): void
    {
        $forge = \Config\Database::forge();
        
        // 1. Adicionar campo situacoes_fiscais na tabela receita_import_tasks
        $fields = [
            'situacoes_fiscais' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Situações fiscais filtradas (ex: 01,02,03)',
                'after' => 'ufs',
            ],
        ];
        
        $forge->addColumn('receita_import_tasks', $fields);
        
        // 2. Criar índices otimizados na tabela estabelecimentos
        // Verificar se tabela existe antes de criar índices
        if ($this->db->tableExists('estabelecimentos')) {
            // Índice para busca por nome
            if (!$this->indexExists('estabelecimentos', 'idx_nome_fantasia')) {
                $this->db->query('CREATE INDEX idx_nome_fantasia ON estabelecimentos(nome_fantasia(100))');
            }
            if (!$this->indexExists('estabelecimentos', 'idx_razao_social')) {
                $this->db->query('CREATE INDEX idx_razao_social ON estabelecimentos(razao_social(100))');
            }
            
            // Índice para busca por CNPJ
            if (!$this->indexExists('estabelecimentos', 'idx_cnpj_completo')) {
                $this->db->query('CREATE INDEX idx_cnpj_completo ON estabelecimentos(cnpj_basico, cnpj_ordem, cnpj_dv)');
            }
            if (!$this->indexExists('estabelecimentos', 'idx_cnpj_basico')) {
                $this->db->query('CREATE INDEX idx_cnpj_basico ON estabelecimentos(cnpj_basico)');
            }
            
            // Índice para busca por CNAE
            if (!$this->indexExists('estabelecimentos', 'idx_cnae_fiscal_principal')) {
                $this->db->query('CREATE INDEX idx_cnae_fiscal_principal ON estabelecimentos(cnae_fiscal_principal)');
            }
            if (!$this->indexExists('estabelecimentos', 'idx_cnae_fiscal_secundario')) {
                $this->db->query('CREATE INDEX idx_cnae_fiscal_secundario ON estabelecimentos(cnae_fiscal_secundario(100))');
            }
            
            // Índice para busca por UF
            if (!$this->indexExists('estabelecimentos', 'idx_uf')) {
                $this->db->query('CREATE INDEX idx_uf ON estabelecimentos(uf)');
            }
            
            // Índice para situação cadastral
            if (!$this->indexExists('estabelecimentos', 'idx_situacao_cadastral')) {
                $this->db->query('CREATE INDEX idx_situacao_cadastral ON estabelecimentos(situacao_cadastral)');
            }
            
            // Índice composto para filtros combinados
            if (!$this->indexExists('estabelecimentos', 'idx_busca_combinada')) {
                $this->db->query('CREATE INDEX idx_busca_combinada ON estabelecimentos(uf, situacao_cadastral, cnae_fiscal_principal)');
            }
        }
        
        // 3. Criar foreign keys se as tabelas relacionadas existirem
        // Nota: Assumindo estrutura baseada no layout da Receita Federal
        // Foreign keys só serão criadas se as tabelas de referência existirem
        
        if ($this->db->tableExists('estabelecimentos') && $this->db->tableExists('empresas')) {
            // FK: estabelecimentos.cnpj_basico -> empresas.cnpj_basico
            if (!$this->foreignKeyExists('estabelecimentos', 'fk_estabelecimentos_empresas')) {
                $this->db->query('
                    ALTER TABLE estabelecimentos 
                    ADD CONSTRAINT fk_estabelecimentos_empresas 
                    FOREIGN KEY (cnpj_basico) 
                    REFERENCES empresas(cnpj_basico) 
                    ON DELETE CASCADE 
                    ON UPDATE CASCADE
                ');
            }
        }
        
        if ($this->db->tableExists('socios') && $this->db->tableExists('empresas')) {
            // FK: socios.cnpj_basico -> empresas.cnpj_basico
            if (!$this->foreignKeyExists('socios', 'fk_socios_empresas')) {
                $this->db->query('
                    ALTER TABLE socios 
                    ADD CONSTRAINT fk_socios_empresas 
                    FOREIGN KEY (cnpj_basico) 
                    REFERENCES empresas(cnpj_basico) 
                    ON DELETE CASCADE 
                    ON UPDATE CASCADE
                ');
            }
        }
        
        log_message('info', 'Migration 5: Campo situacoes_fiscais, índices e foreign keys criados com sucesso');
    }

    public function down(): void
    {
        $forge = \Config\Database::forge();
        
        // Remover foreign keys
        if ($this->db->tableExists('estabelecimentos') && $this->foreignKeyExists('estabelecimentos', 'fk_estabelecimentos_empresas')) {
            $this->db->query('ALTER TABLE estabelecimentos DROP FOREIGN KEY fk_estabelecimentos_empresas');
        }
        
        if ($this->db->tableExists('socios') && $this->foreignKeyExists('socios', 'fk_socios_empresas')) {
            $this->db->query('ALTER TABLE socios DROP FOREIGN KEY fk_socios_empresas');
        }
        
        // Remover índices
        if ($this->db->tableExists('estabelecimentos')) {
            $indexes = ['idx_nome_fantasia', 'idx_razao_social', 'idx_cnpj_completo', 'idx_cnpj_basico', 
                        'idx_cnae_fiscal_principal', 'idx_cnae_fiscal_secundario', 'idx_uf', 
                        'idx_situacao_cadastral', 'idx_busca_combinada'];
            
            foreach ($indexes as $index) {
                if ($this->indexExists('estabelecimentos', $index)) {
                    $this->db->query("DROP INDEX {$index} ON estabelecimentos");
                }
            }
        }
        
        // Remover campo
        $forge->dropColumn('receita_import_tasks', 'situacoes_fiscais');
        
        log_message('info', 'Migration 5: Rollback concluído');
    }
    
    /**
     * Verificar se índice existe
     */
    private function indexExists(string $table, string $index): bool
    {
        $query = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return $query->getNumRows() > 0;
    }
    
    /**
     * Verificar se foreign key existe
     */
    private function foreignKeyExists(string $table, string $fkName): bool
    {
        $database = $this->db->getDatabase();
        $query = $this->db->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$database, $table, $fkName]);
        
        return $query->getNumRows() > 0;
    }
}
