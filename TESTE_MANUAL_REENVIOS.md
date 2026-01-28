# Guia de Teste Manual - Correção de Duplicação de Reenvios

## 📋 Objetivo

Validar que a correção aplicada no `QueueManager.php` previne a duplicação de reenvios automáticos quando o cron é executado múltiplas vezes.

---

## 🔧 Preparação

### 1. Atualizar o código
```bash
cd /caminho/do/projeto
git pull origin Manus
```

### 2. Limpar dados de teste anteriores (opcional)
```sql
-- Limpar apenas dados de teste, não apagar tudo!
DELETE FROM message_sends WHERE message_id = [ID_DA_MENSAGEM_DE_TESTE];
DELETE FROM resend_rules WHERE message_id = [ID_DA_MENSAGEM_DE_TESTE];
```

---

## 🧪 Cenário de Teste

### Passo 1: Criar uma mensagem de teste

1. Acesse o sistema via navegador
2. Crie uma nova mensagem com:
   - Nome: "Teste Reenvio - [Data/Hora]"
   - Assunto: "Teste de Reenvio"
   - Conteúdo: Qualquer HTML válido com link de opt-out
   - Selecione 3-5 contatos ativos
   - Configure 1 reenvio para daqui a 2 minutos

3. Envie a mensagem (não agende, envie imediatamente)

4. Anote o **ID da mensagem** criada (aparece na URL ou na listagem)

---

### Passo 2: Aguardar envio original

1. Execute o cron manualmente ou aguarde 1 minuto:
   ```bash
   php spark queue:process
   ```

2. Verifique no banco de dados que os envios originais foram criados:
   ```sql
   SELECT COUNT(*) as total, status
   FROM message_sends
   WHERE message_id = [ID_DA_MENSAGEM]
     AND resend_number = 0
   GROUP BY status;
   ```

   **Resultado esperado:**
   - Status `sent`: 3-5 registros (dependendo de quantos contatos você selecionou)

---

### Passo 3: Verificar regra de reenvio

1. Verifique que a regra de reenvio foi criada:
   ```sql
   SELECT * FROM resend_rules
   WHERE message_id = [ID_DA_MENSAGEM];
   ```

   **Resultado esperado:**
   - 1 registro com `status = 'pending'`
   - `scheduled_at` com a data/hora futura que você configurou

2. **Ajuste a data para o passado** (para forçar o processamento imediato):
   ```sql
   UPDATE resend_rules
   SET scheduled_at = NOW() - INTERVAL 1 MINUTE
   WHERE message_id = [ID_DA_MENSAGEM];
   ```

---

### Passo 4: Executar cron múltiplas vezes (TESTE PRINCIPAL)

1. Execute o cron **3 vezes seguidas** com intervalo de 5 segundos:
   ```bash
   php spark queue:process
   sleep 5
   php spark queue:process
   sleep 5
   php spark queue:process
   ```

2. Verifique quantos envios de reenvio foram criados:
   ```sql
   SELECT 
       contact_id,
       COUNT(*) as total_envios
   FROM message_sends
   WHERE message_id = [ID_DA_MENSAGEM]
     AND resend_number = 1
   GROUP BY contact_id
   ORDER BY contact_id;
   ```

   **✅ RESULTADO ESPERADO (CORRETO):**
   - Cada contato deve ter **exatamente 1 envio** com `resend_number = 1`
   - Total de registros = número de contatos que NÃO abriram o email original

   **❌ RESULTADO INCORRETO (BUG):**
   - Contatos com 2 ou 3 envios duplicados
   - Exemplo: `contact_id = 5, total_envios = 3`

3. Verifique o status da regra de reenvio:
   ```sql
   SELECT status FROM resend_rules
   WHERE message_id = [ID_DA_MENSAGEM];
   ```

   **✅ RESULTADO ESPERADO:**
   - `status = 'completed'`

---

### Passo 5: Verificar na interface

1. Acesse a visualização da mensagem no sistema
2. Vá até a seção "Últimos Envios"
3. Verifique que:
   - Cada contato aparece apenas **uma vez** na lista de reenvios
   - Não há duplicatas visíveis

---

## 📊 Queries de Diagnóstico

### Verificar duplicatas por contato
```sql
SELECT 
    contact_id,
    resend_number,
    COUNT(*) as duplicatas,
    GROUP_CONCAT(id) as ids_duplicados
FROM message_sends
WHERE message_id = [ID_DA_MENSAGEM]
GROUP BY contact_id, resend_number
HAVING COUNT(*) > 1;
```

**Resultado esperado:** Nenhum registro (sem duplicatas)

---

### Ver todos os envios da mensagem
```sql
SELECT 
    ms.id,
    ms.contact_id,
    c.email,
    ms.resend_number,
    ms.status,
    ms.sent_at,
    ms.opened
FROM message_sends ms
JOIN contacts c ON c.id = ms.contact_id
WHERE ms.message_id = [ID_DA_MENSAGEM]
ORDER BY ms.contact_id, ms.resend_number;
```

---

### Ver histórico de regras de reenvio
```sql
SELECT 
    id,
    message_id,
    resend_number,
    scheduled_at,
    status,
    created_at
FROM resend_rules
WHERE message_id = [ID_DA_MENSAGEM]
ORDER BY resend_number;
```

---

## ✅ Critérios de Sucesso

A correção está funcionando corretamente se:

1. ✅ Cada contato recebe **exatamente 1 envio** por `resend_number`
2. ✅ Não há registros duplicados na tabela `message_sends`
3. ✅ A regra de reenvio é marcada como `completed` após o primeiro processamento
4. ✅ Executar o cron múltiplas vezes **não cria novos envios** para a mesma regra
5. ✅ A interface mostra apenas 1 envio por contato por reenvio

---

## 🐛 Se o teste falhar

Se ainda houver duplicação:

1. Verifique se o código foi atualizado corretamente:
   ```bash
   git log --oneline -1
   # Deve mostrar: "fix: Corrige duplicação de reenvios automáticos"
   ```

2. Verifique se há cache de código:
   ```bash
   # Limpar cache do CodeIgniter
   rm -rf writable/cache/*
   ```

3. Verifique os logs:
   ```bash
   tail -f writable/logs/log-*.log
   ```

4. Execute o script de teste automatizado:
   ```bash
   php test_resend_duplication.php
   ```

---

## 📝 Notas Importantes

- **Não abra os emails** durante o teste, pois isso afeta quem recebe reenvios
- Use contatos de teste, não contatos reais
- O teste pode ser repetido com diferentes mensagens
- Sempre verifique o banco de dados, não apenas a interface

---

## 🎯 Exemplo de Resultado Correto

```
mysql> SELECT contact_id, resend_number, COUNT(*) as total
       FROM message_sends
       WHERE message_id = 42
       GROUP BY contact_id, resend_number;

+------------+---------------+-------+
| contact_id | resend_number | total |
+------------+---------------+-------+
|          5 |             0 |     1 |
|          5 |             1 |     1 |  ← Apenas 1 reenvio
|          7 |             0 |     1 |
|          7 |             1 |     1 |  ← Apenas 1 reenvio
|          9 |             0 |     1 |
|          9 |             1 |     1 |  ← Apenas 1 reenvio
+------------+---------------+-------+
```

**✅ Perfeito!** Cada combinação de `contact_id` + `resend_number` aparece apenas 1 vez.

---

## 🔄 Teste de Múltiplos Reenvios

Para testar com 2 ou 3 reenvios:

1. Configure a mensagem com múltiplos reenvios
2. Ajuste todas as datas para o passado:
   ```sql
   UPDATE resend_rules
   SET scheduled_at = NOW() - INTERVAL 1 MINUTE
   WHERE message_id = [ID_DA_MENSAGEM];
   ```

3. Execute o cron múltiplas vezes
4. Verifique que cada `resend_number` foi processado apenas uma vez

---

**Data do teste:** _____________

**Resultado:** ⬜ Passou ⬜ Falhou

**Observações:**
