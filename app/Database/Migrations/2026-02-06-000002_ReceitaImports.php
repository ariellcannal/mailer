<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;

/**
 * Criar tabelas para importação da Receita
 */
class ReceitaImports
{

    protected BaseConnection $db;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $forge = \Config\Database::forge();

        // Criar tabela receita_import_tasks
        $forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Nome opcional da tarefa'
            ],
            'cnaes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'CNAEs filtrados (JSON array)'
            ],
            'ufs' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Estados filtrados (JSON array)'
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => [
                    'agendada',
                    'em_andamento',
                    'concluida',
                    'erro'
                ],
                'default' => 'agendada',
                'comment' => 'Status da tarefa'
            ],
            'total_files' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Total de arquivos a processar'
            ],
            'processed_files' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Arquivos já processados'
            ],
            'current_file' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Arquivo sendo processado atualmente'
            ],
            'total_lines' => [
                'type' => 'BIGINT',
                'default' => 0,
                'comment' => 'Total estimado de linhas'
            ],
            'processed_lines' => [
                'type' => 'BIGINT',
                'default' => 0,
                'comment' => 'Linhas já processadas'
            ],
            'imported_lines' => [
                'type' => 'BIGINT',
                'default' => 0,
                'comment' => 'Linhas efetivamente importadas'
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Mensagem de erro se houver'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Quando o processamento iniciou'
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Quando o processamento terminou'
            ]
        ]);

        $queries = [
            "CREATE TABLE IF NOT EXISTS receita_paises (codigo INT PRIMARY KEY, descricao VARCHAR(255)) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS receita_municipios (codigo INT PRIMARY KEY, descricao VARCHAR(255)) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS receita_qualificacoes (codigo INT PRIMARY KEY, descricao VARCHAR(255)) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS receita_naturezas (codigo INT PRIMARY KEY, descricao VARCHAR(255)) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS receita_motivos (codigo INT PRIMARY KEY, descricao VARCHAR(255)) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS receita_cnaes (codigo INT PRIMARY KEY, descricao VARCHAR(255)) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS receita_estabelecimentos (
                cnpj_basico CHAR(8), cnpj_ordem CHAR(4), cnpj_dv CHAR(2), matriz_filial INT,
                nome_fantasia VARCHAR(255), situacao_cadastral INT, data_situacao_cadastral DATE,
                motivo_situacao_cadastral INT, nome_cidade_exterior VARCHAR(255), pais INT,
                data_inicio_atividade DATE, cnae_fiscal_principal INT, cnae_fiscal_secundario TEXT,
                tipo_logradouro VARCHAR(50), logradouro VARCHAR(255), numero VARCHAR(50),
                complemento VARCHAR(255), bairro VARCHAR(255), cep VARCHAR(10), uf VARCHAR(2),
                municipio INT, ddd1 VARCHAR(5), telefone1 VARCHAR(20), ddd2 VARCHAR(5),
                telefone2 VARCHAR(20), ddd_fax VARCHAR(5), fax VARCHAR(20), correio_eletronico VARCHAR(255),
                situacao_especial VARCHAR(255), data_situacao_especial DATE,
                PRIMARY KEY (cnpj_basico, cnpj_ordem, cnpj_dv)
            ) ENGINE=InnoDB;",
            "CREATE TABLE IF NOT EXISTS receita_socios (
                cnpj_basico CHAR(8), identificador_socio INT, nome_socio VARCHAR(255),
                cnpj_cpf_socio VARCHAR(20), qualificacao_socio INT, data_entrada_sociedade DATE,
                pais INT, representante_legal VARCHAR(20), nome_representante VARCHAR(255),
                qualificacao_representante_legal INT, faixa_etaria INT,
                INDEX (cnpj_basico)
            ) ENGINE=InnoDB;"
        ];

        foreach ($queries as $q)
            $conn->query($q);

        $forge->addKey('id', true);
        $forge->addKey('status');
        $forge->addKey('created_at');

        $forge->createTable('receita_import_tasks');

        log_message('info', 'ReceitaImports: Tabela receita_import_tasks criada com sucesso');
    }

    public function down(): void
    {
        $forge = \Config\Database::forge();
        $forge->dropTable('receita_import_tasks', true);
        $forge->dropTable('receita_paises', true);
        $forge->dropTable('receita_municipios', true);
        $forge->dropTable('receita_qualificacoes', true);
        $forge->dropTable('receita_naturezas', true);
        $forge->dropTable('receita_motivos', true);
        $forge->dropTable('receita_cnaes', true);
        $forge->dropTable('receita_estabelecimentos', true);
        $forge->dropTable('receita_socios', true);
        log_message('info', 'ReceitaImports: Tabela receita_import_tasks removida');
    }
}
