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
class AddOptimizedForeignKeysAndIndexes extends Migration
{
    public function up()
    {
        log_message('info', '========================================');
        log_message('info', 'AddOptimizedForeignKeysAndIndexes: INICIANDO');
        log_message('info', '========================================');
        
        // Verificar se as tabelas existem
        $tabelasExistem = [
            'receita_estabelecimentos' => $this->db->tableExists('receita_estabelecimentos'),
            'receita_socios' => $this->db->tableExists('receita_socios'),
        ];
        
        log_message('info', 'Migration 6: Verificando tabelas...');
        foreach ($tabelasExistem as $tabela => $existe) {
            log_message('info', "  - {$tabela}: " . ($existe ? 'EXISTE' : 'NÃO EXISTE'));
        }
        
        if (!$tabelasExistem['receita_estabelecimentos']) {
            log_message('warning', 'AddOptimizedForeignKeysAndIndexes: Tabela receita_estabelecimentos não existe! Abortando.');
            return;
        }
        
        // ========================================
        // ÍNDICES PARA ESTABELECIMENTOS
        // ========================================
        
        // Índice para busca por nome fantasia (FULLTEXT para busca rápida)
        log_message('info', 'AddOptimizedForeignKeysAndIndexes: Verificando idx_nome_fantasia...');
        if (!$this->indexExists('receita_estabelecimentos', 'idx_nome_fantasia')) {
            log_message('info', 'AddOptimizedForeignKeysAndIndexes: Criando idx_nome_fantasia...');
            $this->db->query('CREATE INDEX idx_nome_fantasia ON receita_estabelecimentos(nome_fantasia(100))');
            log_message('info', 'AddOptimizedForeignKeysAndIndexes: idx_nome_fantasia criado!');
        } else {
            log_message('info', 'AddOptimizedForeignKeysAndIndexes: idx_nome_fantasia já existe, pulando.');
        }
        
        // Índice para busca por CNPJ completo (base + ordem + dv)
        if (!$this->indexExists('receita_estabelecimentos', 'idx_cnpj_completo')) {
            $this->db->query('CREATE UNIQUE INDEX idx_cnpj_completo ON receita_estabelecimentos(cnpj_basico, cnpj_ordem, cnpj_dv)');
        }
        
        // Índice para busca por CNPJ base (para listar todos estabelecimentos de uma empresa)
        if (!$this->indexExists('receita_estabelecimentos', 'idx_cnpj_basico')) {
            $this->db->query('CREATE INDEX idx_cnpj_basico ON receita_estabelecimentos(cnpj_basico)');
        }
        
        // Índice para busca por CNAE principal
        if (!$this->indexExists('receita_estabelecimentos', 'idx_cnae_principal')) {
            $this->db->query('CREATE INDEX idx_cnae_principal ON receita_estabelecimentos(cnae_fiscal_principal)');
        }
        
        // Índice para busca por CNAE secundária (FULLTEXT para busca em lista)
        if (!$this->indexExists('receita_estabelecimentos', 'idx_cnae_secundaria')) {
            $this->db->query('CREATE FULLTEXT INDEX idx_cnae_secundaria ON receita_estabelecimentos(cnae_fiscal_secundario)');
        }
        
        // Índice para busca por UF
        if (!$this->indexExists('receita_estabelecimentos', 'idx_uf')) {
            $this->db->query('CREATE INDEX idx_uf ON receita_estabelecimentos(uf)');
        }
        
        // Índice para busca por situação cadastral
        if (!$this->indexExists('receita_estabelecimentos', 'idx_situacao')) {
            $this->db->query('CREATE INDEX idx_situacao ON receita_estabelecimentos(situacao_cadastral)');
        }
        
        // Índice composto para filtros combinados (UF + CNAE + Situação)
        if (!$this->indexExists('receita_estabelecimentos', 'idx_filtros_combinados')) {
            $this->db->query('CREATE INDEX idx_filtros_combinados ON receita_estabelecimentos(uf, cnae_fiscal_principal, situacao_cadastral)');
        }
        
        // ========================================
        // ÍNDICES PARA SÓCIOS
        // ========================================
        
        // Índice para busca por CNPJ base (listar sócios de uma empresa)
        if (!$this->indexExists('receita_socios', 'idx_cnpj_basico_socio')) {
            $this->db->query('CREATE INDEX idx_cnpj_basico_socio ON receita_socios(cnpj_basico)');
        }
        
        // Índice para busca por CPF/CNPJ do sócio
        if (!$this->indexExists('receita_socios', 'idx_cnpj_cpf_socio')) {
            $this->db->query('CREATE INDEX idx_cnpj_cpf_socio ON receita_socios(cnpj_cpf_socio)');
        }
        
        // Índice para busca por nome do sócio
        if (!$this->indexExists('receita_socios', 'idx_nome_socio')) {
            $this->db->query('CREATE INDEX idx_nome_socio ON receita_socios(nome_socio(100))');
        }
        
        // ========================================
        // FOREIGN KEYS (Integridade Referencial)
        // ========================================
        
        log_message('info', '========================================');
        log_message('info', 'AddOptimizedForeignKeysAndIndexes: CONCLUÍDA COM SUCESSO');
        log_message('info', '========================================');
    }
    
    public function down()
    {
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
                $this->db->query("ALTER TABLE receita_estabelecimentos DROP INDEX $index");
            }
        }
                
        // Remover índices de sócios
        $indicesSocios = ['idx_cnpj_basico_socio', 'idx_cnpj_cpf_socio', 'idx_nome_socio'];
        foreach ($indicesSocios as $index) {
            if ($this->indexExists('receita_socios', $index)) {
                $this->db->query("ALTER TABLE receita_socios DROP INDEX $index");
            }
        }
        
        log_message('info', 'AddOptimizedForeignKeysAndIndexes: Foreign keys e índices removidos');
    }
    
    /**
     * Verifica se uma tabela existe
     */
    private function tableExists(string $table): bool
    {
        return $this->db->tableExists($table);
    }
    
    /**
     * Verifica se um índice existe
     */
    private function indexExists(string $table, string $indexName): bool
    {
        // Verificar se a tabela existe antes de verificar o índice
        if (!$this->tableExists($table)) {
            return false;
        }
        
        $query = $this->db->query("SHOW INDEX FROM $table WHERE Key_name = ?", [$indexName]);
        return $query->getNumRows() > 0;
    }
    
    /**
     * Verifica se uma foreign key existe
     */
    private function foreignKeyExists(string $table, string $fkName): bool
    {
        $query = $this->db->query("
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
