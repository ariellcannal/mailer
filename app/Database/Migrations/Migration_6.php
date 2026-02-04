<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 6: Adicionar Foreign Keys e Índices Otimizados
 * 
 * Objetivo: Garantir integridade referencial e otimizar buscas por:
 * - Nome (razão social e nome fantasia)
 * - CNAE (principal e secundário)
 * - CNPJ (base, completo)
 */
class Migration_6 extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();
        
        // ========================================
        // ÍNDICES PARA ESTABELECIMENTOS
        // ========================================
        
        // Índice para busca por nome fantasia (FULLTEXT para busca rápida)
        if (!$this->indexExists('receita_estabelecimentos', 'idx_nome_fantasia')) {
            $db->query('CREATE INDEX idx_nome_fantasia ON receita_estabelecimentos(nome_fantasia(100))');
        }
        
        // Índice para busca por CNPJ completo (base + ordem + dv)
        if (!$this->indexExists('receita_estabelecimentos', 'idx_cnpj_completo')) {
            $db->query('CREATE UNIQUE INDEX idx_cnpj_completo ON receita_estabelecimentos(cnpj_basico, cnpj_ordem, cnpj_dv)');
        }
        
        // Índice para busca por CNPJ base (para listar todos estabelecimentos de uma empresa)
        if (!$this->indexExists('receita_estabelecimentos', 'idx_cnpj_basico')) {
            $db->query('CREATE INDEX idx_cnpj_basico ON receita_estabelecimentos(cnpj_basico)');
        }
        
        // Índice para busca por CNAE principal
        if (!$this->indexExists('receita_estabelecimentos', 'idx_cnae_principal')) {
            $db->query('CREATE INDEX idx_cnae_principal ON receita_estabelecimentos(cnae_fiscal_principal)');
        }
        
        // Índice para busca por CNAE secundária (FULLTEXT para busca em lista)
        if (!$this->indexExists('receita_estabelecimentos', 'idx_cnae_secundaria')) {
            $db->query('CREATE FULLTEXT INDEX idx_cnae_secundaria ON receita_estabelecimentos(cnae_fiscal_secundaria)');
        }
        
        // Índice para busca por UF
        if (!$this->indexExists('receita_estabelecimentos', 'idx_uf')) {
            $db->query('CREATE INDEX idx_uf ON receita_estabelecimentos(uf)');
        }
        
        // Índice para busca por situação cadastral
        if (!$this->indexExists('receita_estabelecimentos', 'idx_situacao')) {
            $db->query('CREATE INDEX idx_situacao ON receita_estabelecimentos(situacao_cadastral)');
        }
        
        // Índice composto para filtros combinados (UF + CNAE + Situação)
        if (!$this->indexExists('receita_estabelecimentos', 'idx_filtros_combinados')) {
            $db->query('CREATE INDEX idx_filtros_combinados ON receita_estabelecimentos(uf, cnae_fiscal_principal, situacao_cadastral)');
        }
        
        // ========================================
        // ÍNDICES PARA EMPRESAS
        // ========================================
        
        // Índice para busca por razão social (FULLTEXT)
        if (!$this->indexExists('receita_empresas', 'idx_razao_social')) {
            $db->query('CREATE FULLTEXT INDEX idx_razao_social ON receita_empresas(razao_social)');
        }
        
        // Índice único para CNPJ base (chave primária)
        if (!$this->indexExists('receita_empresas', 'idx_cnpj_basico_empresa')) {
            $db->query('CREATE UNIQUE INDEX idx_cnpj_basico_empresa ON receita_empresas(cnpj_basico)');
        }
        
        // ========================================
        // ÍNDICES PARA SÓCIOS
        // ========================================
        
        // Índice para busca por CNPJ base (listar sócios de uma empresa)
        if (!$this->indexExists('receita_socios', 'idx_cnpj_basico_socio')) {
            $db->query('CREATE INDEX idx_cnpj_basico_socio ON receita_socios(cnpj_basico)');
        }
        
        // Índice para busca por CPF/CNPJ do sócio
        if (!$this->indexExists('receita_socios', 'idx_cpf_cnpj_socio')) {
            $db->query('CREATE INDEX idx_cpf_cnpj_socio ON receita_socios(cpf_cnpj_socio)');
        }
        
        // Índice para busca por nome do sócio
        if (!$this->indexExists('receita_socios', 'idx_nome_socio')) {
            $db->query('CREATE INDEX idx_nome_socio ON receita_socios(nome_socio_razao_social(100))');
        }
        
        // ========================================
        // FOREIGN KEYS (Integridade Referencial)
        // ========================================
        
        // FK: estabelecimentos.cnpj_basico → empresas.cnpj_basico
        if (!$this->foreignKeyExists('receita_estabelecimentos', 'fk_estabelecimento_empresa')) {
            $db->query('
                ALTER TABLE receita_estabelecimentos 
                ADD CONSTRAINT fk_estabelecimento_empresa 
                FOREIGN KEY (cnpj_basico) 
                REFERENCES receita_empresas(cnpj_basico) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE
            ');
        }
        
        // FK: socios.cnpj_basico → empresas.cnpj_basico
        if (!$this->foreignKeyExists('receita_socios', 'fk_socio_empresa')) {
            $db->query('
                ALTER TABLE receita_socios 
                ADD CONSTRAINT fk_socio_empresa 
                FOREIGN KEY (cnpj_basico) 
                REFERENCES receita_empresas(cnpj_basico) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE
            ');
        }
        
        log_message('info', 'Migration 6: Foreign keys e índices criados com sucesso');
    }
    
    public function down()
    {
        $db = \Config\Database::connect();
        
        // Remover foreign keys
        if ($this->foreignKeyExists('receita_estabelecimentos', 'fk_estabelecimento_empresa')) {
            $db->query('ALTER TABLE receita_estabelecimentos DROP FOREIGN KEY fk_estabelecimento_empresa');
        }
        
        if ($this->foreignKeyExists('receita_socios', 'fk_socio_empresa')) {
            $db->query('ALTER TABLE receita_socios DROP FOREIGN KEY fk_socio_empresa');
        }
        
        // Remover índices de estabelecimentos
        $indices = [
            'idx_nome_fantasia',
            'idx_cnpj_completo',
            'idx_cnpj_basico',
            'idx_cnae_principal',
            'idx_cnae_secundaria',
            'idx_uf',
            'idx_situacao',
            'idx_filtros_combinados'
        ];
        
        foreach ($indices as $index) {
            if ($this->indexExists('receita_estabelecimentos', $index)) {
                $db->query("ALTER TABLE receita_estabelecimentos DROP INDEX $index");
            }
        }
        
        // Remover índices de empresas
        if ($this->indexExists('receita_empresas', 'idx_razao_social')) {
            $db->query('ALTER TABLE receita_empresas DROP INDEX idx_razao_social');
        }
        if ($this->indexExists('receita_empresas', 'idx_cnpj_basico_empresa')) {
            $db->query('ALTER TABLE receita_empresas DROP INDEX idx_cnpj_basico_empresa');
        }
        
        // Remover índices de sócios
        $indicesSocios = ['idx_cnpj_basico_socio', 'idx_cpf_cnpj_socio', 'idx_nome_socio'];
        foreach ($indicesSocios as $index) {
            if ($this->indexExists('receita_socios', $index)) {
                $db->query("ALTER TABLE receita_socios DROP INDEX $index");
            }
        }
        
        log_message('info', 'Migration 6: Foreign keys e índices removidos');
    }
    
    /**
     * Verifica se um índice existe
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $db = \Config\Database::connect();
        $query = $db->query("SHOW INDEX FROM $table WHERE Key_name = ?", [$indexName]);
        return $query->getNumRows() > 0;
    }
    
    /**
     * Verifica se uma foreign key existe
     */
    private function foreignKeyExists(string $table, string $fkName): bool
    {
        $db = \Config\Database::connect();
        $query = $db->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $fkName]);
        return $query->getNumRows() > 0;
    }
}
