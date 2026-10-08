# Changelog de Correções - CANNAL Envios

## [2026-01-28] Correção de Duplicação de Reenvios Automáticos

### 🐛 Problema Identificado

Os reenvios automáticos estavam sendo enviados em duplicidade para cada contato. Quando o cron `queue:process` era executado múltiplas vezes (a cada minuto), o mesmo agendamento de reenvio criava múltiplos envios para os mesmos contatos.

**Exemplo do bug:**
- Contato ID 5 deveria receber 1 reenvio
- Na prática, recebia 2 ou 3 reenvios duplicados
- Cada execução do cron criava novos registros na tabela `message_sends`

### 🔍 Causa Raiz

No arquivo `app/Libraries/Email/QueueManager.php`, método `queueResendsDue()` (linha 629-703):

O sistema verificava se já existiam envios para aquele `resend_number` antes de criar novos (linha 684-691), **MAS** não marcava a regra de reenvio como `completed` quando detectava duplicatas.

Isso causava o seguinte fluxo:

1. **Execução 1 do cron:**
   - Regra de reenvio está `pending`
   - Verifica: não existem envios para `resend_number = 1`
   - Cria envios para todos os contatos
   - Marca regra como `completed`

2. **Execução 2 do cron (antes da correção):**
   - Se a execução 1 ainda não terminou de marcar como `completed`
   - Regra ainda aparece como `pending`
   - Verifica: já existem envios, mas **não marca como completed**
   - Pula para próxima iteração

3. **Execução 3 do cron:**
   - Regra AINDA está `pending` (não foi marcada na execução 2)
   - Cria envios duplicados novamente

### ✅ Solução Aplicada

Adicionado código para marcar a regra como `completed` quando detecta que já existem envios:

```php
// Verificar se já existem envios para este resend_number
$existing = $this->sendModel
    ->where('message_id', $messageId)
    ->where('resend_number', $rule['resend_number'])
    ->countAllResults();

if ($existing > 0) {
    // ✅ NOVO: Já existem envios para este reenvio, marcar regra como completa
    $db->table('resend_rules')
        ->where('id', $rule['id'])
        ->update(['status' => 'completed']);
    continue;
}
```

### 📝 Arquivos Modificados

- `app/Libraries/Email/QueueManager.php` (linhas 690-695)

### 🧪 Testes Criados

1. **Script automatizado:** `test_resend_duplication.php`
   - Simula múltiplas execuções do cron
   - Valida que não há duplicatas
   - Verifica status da regra

2. **Guia de teste manual:** `TESTE_MANUAL_REENVIOS.md`
   - Passo a passo detalhado
   - Queries SQL para diagnóstico
   - Critérios de sucesso

### 🚀 Como Validar

Execute o teste manual seguindo o guia `TESTE_MANUAL_REENVIOS.md` ou execute:

```bash
php test_resend_duplication.php
```

### 📊 Resultado Esperado

Após a correção, cada contato deve receber **exatamente 1 envio** por `resend_number`, mesmo que o cron seja executado múltiplas vezes.

**Query de validação:**
```sql
SELECT 
    contact_id,
    resend_number,
    COUNT(*) as total_envios
FROM message_sends
WHERE message_id = [ID_DA_MENSAGEM]
GROUP BY contact_id, resend_number
HAVING COUNT(*) > 1;
```

**Resultado esperado:** Nenhum registro (sem duplicatas)

---

## [Pendente] Atualização do Schema do Banco de Dados

### 📋 Campos que Precisam ser Adicionados

#### Tabela `contacts`
- `bounced` TINYINT(1) DEFAULT 0
- `bounce_type` VARCHAR(50) NULL
- `bounced_at` DATETIME NULL
- `is_active` TINYINT(1) DEFAULT 1

#### Tabela `message_sends`
- `bounce_type` VARCHAR(50) NULL
- `bounce_reason` TEXT NULL
- `bounced_at` DATETIME NULL
- `subject_override` VARCHAR(255) NULL

#### Tabela `messages`
- `status` ENUM('draft', 'scheduled', 'sending', 'sent', 'completed', 'cancelled')

### 📝 Scripts SQL Disponíveis

Os scripts SQL para atualização do banco foram fornecidos separadamente. Execute-os para garantir compatibilidade total com o código.

---

## Commits Relacionados

- `5fb18cc` - fix: Corrige duplicação de reenvios automáticos
- `8b24883` - docs: Adiciona scripts e guia de teste para validação de reenvios

---

**Última atualização:** 28 de Janeiro de 2026
