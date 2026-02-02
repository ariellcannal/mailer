# Configuração do CRON para Importação da Receita Federal

## Visão Geral

O sistema de importação da Receita Federal funciona de forma **assíncrona** através de tarefas processadas via **Spark Command** executado pelo CRON. Isso permite:

- ✅ Processamento em background sem bloquear a interface
- ✅ Controle de concorrência (apenas uma tarefa por vez)
- ✅ Continuidade automática após interrupções
- ✅ Filtros por CNAE e UF para otimizar importações
- ✅ Acompanhamento em tempo real do progresso

## Como Funciona

1. **Usuário agenda uma tarefa** através da interface web
2. **CRON executa a cada minuto** o comando `php spark receita:process`
3. **Command verifica** se há tarefas agendadas e se não há processo em execução
4. **Processa por até 55 segundos** e salva o progresso
5. **Repete até concluir** todas as tarefas agendadas

## Configuração do CRON

### 1. Editar crontab

```bash
crontab -e
```

### 2. Adicionar linha para executar a cada minuto

```bash
* * * * * cd /caminho/completo/do/projeto && php spark receita:process >> /dev/null 2>&1
```

### 3. Com logs (recomendado para debug)

```bash
* * * * * cd /caminho/completo/do/projeto && php spark receita:process >> /var/log/receita-import.log 2>&1
```

### 4. Exemplo completo

```bash
* * * * * cd /var/www/mailer && /usr/bin/php8.1 spark receita:process >> /var/log/receita-import.log 2>&1
```

**Substitua:**
- `/var/www/mailer` pelo caminho real do seu projeto
- `/usr/bin/php8.1` pelo caminho do seu PHP (use `which php` para descobrir)

## Verificar se está Funcionando

### 1. Testar comando manualmente

```bash
cd /caminho/do/projeto
php spark receita:process
```

Você verá output colorido indicando o progresso:
```
[2025-01-30 15:30:00] Iniciando processamento de importações da Receita...
Processando tarefa #1: Contabilidades SP
Processamento parcial da tarefa #1
Progresso: 3/10 arquivos
Linhas processadas: 15000
[2025-01-30 15:30:55] Processamento finalizado.
```

### 2. Verificar logs

```bash
tail -f /var/log/receita-import.log
```

### 3. Verificar lockfile

```bash
ls -la /caminho/do/projeto/receita.lock
```

Se o arquivo existir, significa que há um processo em execução.

### 4. Verificar tarefas no banco

```sql
SELECT id, name, status, processed_files, total_files, processed_lines, total_lines 
FROM receita_import_tasks 
ORDER BY created_at DESC;
```

## Estrutura de Arquivos

```
writable/receita/
├── Cnaes.zip
├── Estabelecimentos0.zip
├── Estabelecimentos1.zip
├── ...
├── process_1          # Arquivo de progresso da tarefa #1
├── process_2          # Arquivo de progresso da tarefa #2
└── ...

/caminho/do/projeto/
└── receita.lock       # Lockfile de controle de concorrência
```

## Fluxo de Processamento

```
┌─────────────────────────────────────────────────────────────┐
│            CRON (a cada minuto)                              │
│            php spark receita:process                         │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
         ┌───────────────────────────────┐
         │  Verificar lockfile           │
         │  - Existe?                    │
         │  - Processo ainda rodando?    │
         └───────────┬───────────────────┘
                     │
                     ▼
         ┌───────────────────────────────┐
         │  Buscar próxima tarefa        │
         │  Status: "agendada"           │
         │  Ordem: created_at ASC        │
         └───────────┬───────────────────┘
                     │
                     ▼
         ┌───────────────────────────────┐
         │  Marcar como "em_andamento"   │
         │  Criar lockfile               │
         └───────────┬───────────────────┘
                     │
                     ▼
         ┌───────────────────────────────┐
         │  Processar arquivos           │
         │  - Baixar se necessário       │
         │  - Aplicar filtros (UF/CNAE)  │
         │  - Inserir em lote (500)      │
         │  - Salvar progresso           │
         └───────────┬───────────────────┘
                     │
                     ▼
         ┌───────────────────────────────┐
         │  Verificar tempo (55s?)       │
         │  - Sim: Parar e salvar        │
         │  - Não: Continuar             │
         └───────────┬───────────────────┘
                     │
                     ▼
         ┌───────────────────────────────┐
         │  Concluído?                   │
         │  - Sim: Marcar "concluida"    │
         │  - Não: Aguardar próximo CRON │
         └───────────┬───────────────────┘
                     │
                     ▼
         ┌───────────────────────────────┐
         │  Remover lockfile             │
         │  Excluir process_{id}         │
         └───────────────────────────────┘
```

## Limites e Configurações

| Parâmetro | Valor | Descrição |
|-----------|-------|-----------|
| **Tempo máximo de execução** | 55 segundos | Garante que o CRON não sobreponha processos |
| **Time limit PHP** | 90 segundos | Margem de segurança para finalização |
| **Memory limit** | 128M | Suficiente para processamento em lote |
| **Batch size** | 500 registros | Inserções em lote para performance |
| **Intervalo CRON** | 1 minuto | Frequência de verificação de tarefas |

## Comandos Spark Disponíveis

### receita:process

Processa a próxima tarefa agendada (usado pelo CRON).

```bash
php spark receita:process
```

**Output:**
- Verde: Sucesso
- Amarelo: Avisos (sem tarefas, já em execução)
- Vermelho: Erros

## Troubleshooting

### Problema: Tarefa fica "em_andamento" indefinidamente

**Causa:** Processo foi interrompido sem liberar o lockfile

**Solução:**
```bash
# Remover lockfile órfão
rm /caminho/do/projeto/receita.lock

# Resetar status da tarefa
UPDATE receita_import_tasks SET status = 'agendada' WHERE id = X;
```

### Problema: CRON não está executando

**Verificar:**
1. CRON está rodando? `service cron status`
2. Caminho do PHP está correto? `which php`
3. Caminho do projeto está correto?
4. Logs de erro? `tail -f /var/log/syslog | grep CRON`
5. Testar manualmente: `cd /caminho && php spark receita:process`

### Problema: Comando não encontrado

**Erro:** `Command "receita:process" not found`

**Solução:**
```bash
# Verificar se o arquivo existe
ls -la app/Commands/ProcessReceitaImports.php

# Limpar cache do framework
php spark cache:clear
```

### Problema: Importação muito lenta

**Otimizações:**
1. Usar filtros por UF para reduzir volume
2. Usar filtros por CNAE específicos
3. Aumentar memory_limit se necessário
4. Verificar índices no banco de dados

## Monitoramento

### Via Interface Web

Acesse: `https://seu-dominio.com/receita/tasks`

A página atualiza automaticamente a cada 10 segundos mostrando:
- Status de cada tarefa
- Progresso em tempo real
- Arquivos processados
- Linhas importadas

### Via Logs

```bash
# Logs do CRON
tail -f /var/log/receita-import.log

# Logs da aplicação
tail -f writable/logs/log-*.log | grep Receita
```

## Exemplo de Uso

1. **Agendar importação** de contabilidades de SP:
   - Nome: "Contabilidades SP - Janeiro 2025"
   - CNAEs: 6920-6/01, 6920-6/02
   - UFs: SP
   - Clicar em "Agendar Importação"

2. **Acompanhar progresso** em "Ver Tarefas"

3. **CRON processa automaticamente** nos próximos minutos

4. **Duplicar tarefa** para outros estados se necessário

## Comandos Úteis

```bash
# Testar processamento manualmente
php spark receita:process

# Listar todos os comandos disponíveis
php spark list

# Ver help do comando
php spark help receita:process

# Ver tarefas agendadas
mysql -u user -p -e "SELECT * FROM receita_import_tasks WHERE status='agendada'"

# Ver lockfile
cat /caminho/do/projeto/receita.lock

# Limpar tarefas antigas (concluídas há mais de 30 dias)
mysql -u user -p -e "DELETE FROM receita_import_tasks WHERE status='concluida' AND completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
```

## Segurança

- ✅ Lockfile previne execuções simultâneas
- ✅ Verificação de processo órfão
- ✅ Transações no banco de dados
- ✅ Logs detalhados de erros
- ✅ Limite de tempo para evitar loops infinitos
- ✅ Comando CLI isolado do contexto HTTP

## Performance

Com as otimizações implementadas:

- **Inserção em lote**: 500 registros por vez
- **Garbage collection**: Ativa durante processamento
- **Índices no banco**: Otimizam queries de verificação
- **Filtros aplicados**: Reduzem volume processado
- **Progresso salvo**: A cada batch para continuidade
- **Queries desabilitadas**: saveQueries = false para economia de memória

---

**Última atualização:** 30/01/2025
**Versão:** 3.0 (Spark Command + CRON)
