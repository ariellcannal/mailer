# Project TODO - Mailer CI4

## Novas Funcionalidades

### Sistema de Listas de Contatos na Importação
- [ ] Adicionar campo "Criar Lista de Contatos" no formulário de importação
- [ ] Adicionar checkbox "Adicionar Contatos de Contabilidade à Nova Lista"
- [ ] Remover bloco "Sobre o Processamento Assíncrono"
- [ ] Expandir formulário para largura total
- [ ] Implementar criação de lista de contatos ao agendar importação
- [ ] Implementar criação/atualização de contatos durante processamento
- [ ] Implementar inserção de contatos na lista criada
- [ ] Respeitar checkbox de contabilidade ao inserir contatos

### Toolbar de Navegação
- [ ] Criar componente de toolbar com botões: Voltar, Nova Importação, Ver Tarefas, Registros Importados
- [ ] Aplicar toolbar em todas as views da Receita Federal
- [ ] Testar navegação entre páginas

## Correções Recentes
- [x] Corrigir nome da coluna cnae_fiscal_secundario (estava com "a" no final)
- [x] Adicionar campos total_bytes e processed_bytes aos allowedFields
- [x] Calcular bytes baseado em linhas processadas
- [x] Mover percentual para fora da barra de progresso
- [x] Corrigir caminhos de layout (layout/main → layouts/main)
- [x] Adicionar situacoes_fiscais aos allowedFields


## Status da Implementação

### Fase 1: Formulário - Concluída
- [x] Adicionar campo "Criar Lista de Contatos" no formulário de importação
- [x] Adicionar checkbox "Adicionar Contatos de Contabilidade à Nova Lista"
- [x] Remover bloco "Sobre o Processamento Assíncrono"
- [x] Expandir formulário para largura total
- [x] Atualizar JavaScript para enviar novos campos

### Fase 2: Backend - Concluída
- [x] Adicionar campos ao ReceitaController.schedule()
- [x] Adicionar campos aos allowedFields do Model
- [x] Implementar createContactList() no processador
- [x] Implementar processContactsFromBatch() no processador
- [x] Integrar criação de contatos no fluxo de importação
- [x] Respeitar checkbox de contabilidade

### Fase 3: Toolbar - Concluída
- [x] Criar componente de toolbar
- [x] Aplicar em todas as views da Receita (index, tasks, empresas, empresa_detalhes)


## Nova Tarefa: Corrigir Situação Cadastral (2 dígitos → 1 dígito)

### Problema
A situação cadastral nos arquivos da Receita Federal tem apenas 1 dígito, mas a aplicação está assumindo 2 dígitos.

### Tarefas
- [x] Identificar onde situacao_cadastral é processada na importação
- [x] Corrigir leitura do campo no ReceitaAsyncProcessor (comentário atualizado)
- [x] Corrigir validação no formulário de importação (valores 01,02,03,04,08 → 1,2,3,4,8)
- [x] Corrigir valor padrão no Model (02,03 → 2,3)
- [ ] Testar importação com situação de 1 dígito


## 🚨 Erro Crítico: Conexão com Banco de Dados

### Problema
Desde o commit 1eb2628, qualquer requisição resulta em erro "Unable to connect to the database".

### Tarefas
- [x] Verificar configuração do banco de dados
- [x] Identificar alterações problemáticas no commit 1eb2628 (Database.php com credenciais hardcoded)
- [x] Reverter alterações no Database.php para valores padrão
- [ ] Testar conexão após correção


## Nova Tarefa: Mover Funcionalidade de Listas para View de Empresas

### Objetivo
Remover campos de lista do formulário de importação e transferir para /receita/empresas com interface mais intuitiva.

### Tarefas
- [x] Remover campos "Criar Lista de Contatos" e checkbox de contabilidade do formulário
- [x] Reorganizar formulário: 5 campos por linha (4 campos + botão Agendar)
- [x] Remover botões extras do formulário
- [x] Implementar card na view empresas que aparece quando filtros estão ativos
- [x] Adicionar texto "XX empresas encontradas. Adicionar à lista de contatos"
- [x] Implementar Select2 multi para selecionar/criar listas (com tagging)
- [x] Criar endpoint buscarListasContatos
- [x] Criar endpoint adicionarEmpresasALista
- [x] Ajustar toolbar: "NOVA IMPORTAÇÃO" → "Nova Importação"
- [x] Alinhar toolbar à direita
- [x] Controlar estado "active" na toolbar


## Correções Solicitadas - Interface de Tarefas

- [x] Corrigir capitalização da toolbar ("NOVA IMPORTAÇÃO" → "Nova Importação") - já estava correto, problema de cache
- [x] Adicionar botão "Pausar" na lista de tarefas
- [x] Adicionar coluna "Filtros" mostrando CNAEs, Estados e Situações Fiscais
- [x] Uniformizar largura das barras de progresso (200px fixo)
- [x] Corrigir contagem de linhas importadas (usar affectedRows() em vez de count())
