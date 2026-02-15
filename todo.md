# TODO - Sistema de Email Marketing

## Concluídas
- [x] Fase 1: Estrutura de bounces (bounce_subtype, aws_message_id)
- [x] Fase 4: Toolbar de ações em massa em /contacts
- [x] Fase 6: Paginação melhorada (button dropdown, navegação expandida)
- [x] Sistema de bounces AWS SES completo
- [x] Otimização CRON para t3a.small
- [x] Importação da Receita Federal
- [x] Normalização de dados (trim, lowercase em emails)
- [x] Correção de ambiguidade SQL no getAllContactIds

## Em Andamento

## Corrigido Agora
- [x] Contador de importados agora considera TODOS os contatos do batch (incluindo ignorados)

## Corrigido Agora
- [x] Erro SQL: Criado método insertBatchIgnore() usando Query Builder do CI4

## Concluídas Agora
- [x] Auto-refresh a cada 10s em /contacts/imports quando há processo "Processando"
- [x] Não renomear arquivo de importação (usa nome original, adiciona timestamp se conflito)
- [x] Usar INSERT IGNORE no batch para evitar erro de duplicate entry
- [x] Adicionar contatos às listas em massa ao final do batch (não um por um)

## Concluídas Recentemente
- [x] Importação: Verificar duplicidade por email (adicionar à lista se existir)
- [x] Importação: Adicionar campo "Apelido" no mapeamento (backend pronto, view com problema)

## Pendentes (Conforme PLANO_ACAO_MAILER_FASES_RESTANTES.md)
- [ ] Fase 2: Tabelas de envios e bounces em /contacts/view/
- [ ] Fase 3: Totais de contatos ativos/inativos nas listas
- [ ] Fase 5: Colunas Bounce e OptOut na listagem de contatos
- [ ] Fase 7: Limpeza de código órfão
