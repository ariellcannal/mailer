<?php

/**
 * Script de Diagnóstico do Sistema de Migrations
 * 
 * Execute este script para verificar:
 * - Versão atual do banco de dados
 * - Migrations disponíveis no diretório
 * - Qual migration deveria ser executada
 */

define('ENVIRONMENT', 'development');
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
define('APPPATH', __DIR__ . '/app/');
define('ROOTPATH', __DIR__ . '/');
define('SYSTEMPATH', __DIR__ . '/vendor/codeigniter4/framework/system/');
define('WRITEPATH', __DIR__ . '/writable/');

require __DIR__ . '/vendor/autoload.php';

echo "=== DIAGNÓSTICO DO SISTEMA DE MIGRATIONS ===\n\n";

try {
    // 1. Verificar conexão com banco
    $db = \Config\Database::connect();
    echo "✅ Conexão com banco estabelecida\n";
    echo "   Database: " . $db->database . "\n\n";
    
    // 2. Verificar se tabela system_settings existe
    $tableExists = $db->tableExists('system_settings');
    echo "📋 Tabela system_settings existe: " . ($tableExists ? "SIM" : "NÃO") . "\n\n";
    
    if ($tableExists) {
        // 3. Ler versão atual do banco
        $result = $db->table('system_settings')
            ->where('setting_key', 'db_version')
            ->get()
            ->getRowArray();
        
        if ($result) {
            $currentVersion = (int) $result['setting_value'];
            echo "📊 Versão atual do banco: {$currentVersion}\n\n";
        } else {
            echo "⚠️  Registro db_version não encontrado em system_settings\n\n";
            $currentVersion = 0;
        }
    } else {
        $currentVersion = 0;
        echo "⚠️  Assumindo versão 0 (tabela não existe)\n\n";
    }
    
    // 4. Escanear diretório de migrations
    $migrationsPath = APPPATH . 'Database/Migrations/';
    echo "📁 Diretório de migrations: {$migrationsPath}\n";
    
    if (!is_dir($migrationsPath)) {
        echo "❌ Diretório não existe!\n";
        exit(1);
    }
    
    $files = scandir($migrationsPath);
    $versions = [];
    
    echo "\n📄 Arquivos de migration encontrados:\n";
    foreach ($files as $file) {
        if (preg_match('/^Migration_(\d+)\.php$/', $file, $matches)) {
            $version = (int) $matches[1];
            $versions[] = $version;
            $status = ($version <= $currentVersion) ? "✅ EXECUTADA" : "⏳ PENDENTE";
            echo "   - {$file} (versão {$version}) - {$status}\n";
        }
    }
    
    if (empty($versions)) {
        echo "   ⚠️  Nenhuma migration encontrada!\n";
        exit(1);
    }
    
    sort($versions);
    $latestVersion = max($versions);
    
    echo "\n📊 Resumo:\n";
    echo "   - Versão atual do BD: {$currentVersion}\n";
    echo "   - Última versão disponível: {$latestVersion}\n";
    echo "   - Migrations disponíveis: " . implode(', ', $versions) . "\n\n";
    
    // 5. Determinar quais migrations devem ser executadas
    if ($currentVersion >= $latestVersion) {
        echo "✅ Banco de dados está ATUALIZADO!\n";
        echo "   Nenhuma migration pendente.\n";
    } else {
        echo "⚠️  Banco de dados está DESATUALIZADO!\n";
        echo "   Migrations pendentes:\n";
        for ($v = $currentVersion + 1; $v <= $latestVersion; $v++) {
            if (in_array($v, $versions)) {
                echo "   - Migration_{$v}.php\n";
            } else {
                echo "   - Migration_{$v}.php ❌ ARQUIVO NÃO ENCONTRADO!\n";
            }
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "CONCLUSÃO:\n";
    
    if ($currentVersion >= $latestVersion) {
        echo "O sistema está funcionando corretamente.\n";
        echo "Todas as migrations foram executadas.\n";
    } else {
        echo "O sistema DEVERIA executar as migrations pendentes.\n";
        echo "Se isso não está acontecendo, verifique:\n";
        echo "1. O BaseController está chamando checkDatabaseMigrations()?\n";
        echo "2. Há algum erro sendo suprimido?\n";
        echo "3. O MigrationManager está sendo instanciado corretamente?\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
