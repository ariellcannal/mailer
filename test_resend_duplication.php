<?php

/**
 * Script de Teste: Validação de Correção de Duplicação de Reenvios
 * 
 * Este script testa se a correção aplicada no QueueManager previne
 * a duplicação de reenvios quando o método queueResendsDue é chamado
 * múltiplas vezes.
 * 
 * Cenário de teste:
 * 1. Criar uma mensagem com envio original completo
 * 2. Criar uma regra de reenvio pendente
 * 3. Chamar queueResendsDue múltiplas vezes
 * 4. Verificar que apenas UM conjunto de envios foi criado
 */

// Carrega o framework CodeIgniter
require __DIR__ . '/vendor/autoload.php';

// Bootstrap do CodeIgniter
$pathsPath = realpath(FCPATH . '../app/Config/Paths.php');
$paths = new \Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;
$app->initialize();

use App\Libraries\Email\QueueManager;
use App\Models\MessageModel;
use App\Models\MessageSendModel;
use App\Models\ContactModel;

echo "\n===========================================\n";
echo "TESTE: Validação de Duplicação de Reenvios\n";
echo "===========================================\n\n";

$db = \Config\Database::connect();
$queueManager = new QueueManager();
$messageModel = new MessageModel();
$sendModel = new MessageSendModel();
$contactModel = new ContactModel();

// Limpar dados de teste anteriores
echo "1. Limpando dados de teste anteriores...\n";
$db->table('resend_rules')->where('message_id >', 0)->delete();
$db->table('message_sends')->where('message_id >', 0)->delete();

// Buscar uma mensagem existente para teste
$testMessage = $messageModel->first();

if (!$testMessage) {
    echo "❌ ERRO: Nenhuma mensagem encontrada no banco de dados.\n";
    echo "   Por favor, crie uma mensagem antes de executar este teste.\n\n";
    exit(1);
}

$messageId = (int) $testMessage['id'];
echo "   ✓ Usando mensagem ID: {$messageId}\n\n";

// Buscar contatos para teste
$testContacts = $contactModel
    ->where('is_active', 1)
    ->where('opted_out', 0)
    ->where('bounced', 0)
    ->limit(5)
    ->findAll();

if (count($testContacts) < 2) {
    echo "❌ ERRO: Necessário pelo menos 2 contatos ativos no banco de dados.\n\n";
    exit(1);
}

$contactIds = array_column($testContacts, 'id');
echo "2. Criando envios originais para " . count($contactIds) . " contatos...\n";

// Criar envios originais (resend_number = 0) com status 'sent'
foreach ($contactIds as $contactId) {
    $trackingHash = hash('sha256', $messageId . '-' . $contactId . '-0-' . time() . '-' . rand(1000, 9999));
    
    $sendModel->insert([
        'message_id' => $messageId,
        'contact_id' => $contactId,
        'resend_number' => 0,
        'tracking_hash' => $trackingHash,
        'status' => 'sent',
        'sent_at' => date('Y-m-d H:i:s'),
        'opened' => 0,
        'total_opens' => 0,
        'clicked' => 0,
        'total_clicks' => 0,
    ]);
}

echo "   ✓ Criados " . count($contactIds) . " envios originais\n\n";

// Criar regra de reenvio pendente
echo "3. Criando regra de reenvio pendente...\n";
$db->table('resend_rules')->insert([
    'message_id' => $messageId,
    'resend_number' => 1,
    'subject_override' => 'Teste - Reenvio 1',
    'scheduled_at' => date('Y-m-d H:i:s', strtotime('-1 minute')), // No passado para ser processado
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s'),
]);

$ruleId = $db->insertID();
echo "   ✓ Regra de reenvio criada (ID: {$ruleId})\n\n";

// Contar envios antes do teste
$sendsBefore = $sendModel
    ->where('message_id', $messageId)
    ->where('resend_number', 1)
    ->countAllResults();

echo "4. Envios de reenvio antes do teste: {$sendsBefore}\n\n";

// TESTE: Chamar queueResendsDue múltiplas vezes
echo "5. Chamando queueResendsDue 3 vezes seguidas...\n";

$reflection = new ReflectionClass($queueManager);
$method = $reflection->getMethod('queueResendsDue');
$method->setAccessible(true);

$now = date('Y-m-d H:i:s');

echo "   - Chamada 1...\n";
$method->invoke($queueManager, $now);

echo "   - Chamada 2...\n";
$method->invoke($queueManager, $now);

echo "   - Chamada 3...\n";
$method->invoke($queueManager, $now);

echo "   ✓ Chamadas concluídas\n\n";

// Contar envios após o teste
$sendsAfter = $sendModel
    ->where('message_id', $messageId)
    ->where('resend_number', 1)
    ->countAllResults();

echo "6. Envios de reenvio após o teste: {$sendsAfter}\n\n";

// Verificar status da regra
$ruleStatus = $db->table('resend_rules')
    ->where('id', $ruleId)
    ->get()
    ->getRowArray();

echo "7. Status da regra de reenvio: " . ($ruleStatus['status'] ?? 'DESCONHECIDO') . "\n\n";

// VALIDAÇÃO
echo "===========================================\n";
echo "RESULTADO DO TESTE\n";
echo "===========================================\n\n";

$expectedSends = count($contactIds); // Um envio por contato
$success = true;

if ($sendsAfter !== $expectedSends) {
    echo "❌ FALHOU: Esperado {$expectedSends} envios, encontrado {$sendsAfter}\n";
    $success = false;
} else {
    echo "✓ Número correto de envios criados: {$sendsAfter}\n";
}

if (($ruleStatus['status'] ?? '') !== 'completed') {
    echo "❌ FALHOU: Regra deveria estar 'completed', está '" . ($ruleStatus['status'] ?? 'DESCONHECIDO') . "'\n";
    $success = false;
} else {
    echo "✓ Regra marcada como 'completed'\n";
}

// Verificar se há duplicatas por contato
$duplicates = $db->query("
    SELECT contact_id, COUNT(*) as count
    FROM message_sends
    WHERE message_id = {$messageId}
    AND resend_number = 1
    GROUP BY contact_id
    HAVING count > 1
")->getResultArray();

if (!empty($duplicates)) {
    echo "❌ FALHOU: Encontradas duplicatas para contatos:\n";
    foreach ($duplicates as $dup) {
        echo "   - Contato {$dup['contact_id']}: {$dup['count']} envios\n";
    }
    $success = false;
} else {
    echo "✓ Nenhuma duplicata encontrada\n";
}

echo "\n";

if ($success) {
    echo "🎉 TESTE PASSOU! A correção está funcionando corretamente.\n";
    echo "   Reenvios não estão sendo duplicados.\n\n";
    exit(0);
} else {
    echo "❌ TESTE FALHOU! Ainda há problemas com duplicação.\n\n";
    exit(1);
}
