# CANNAL Envios - Lista de Tarefas

## Novas Funcionalidades

### Sistema de Listas de Contatos na Importação
- [x] **COMPLETO:** Sistema completo de listas de contatos na importação já estava implementado!
- [x] Adicionar campo "Criar Lista de Contatos" no formulário de importação
- [x] Adicionar checkbox "Adicionar Contatos de Contabilidade à Nova Lista"
- [x] Implementar criação de lista de contatos ao agendar importação
- [x] Implementar criação/atualização de contatos durante processamento
- [x] Implementar inserção de contatos na lista criada
- [x] Respeitar checkbox de contabilidade ao inserir contatos

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


## Correções Urgentes - Interface de Tarefas

- [x] Corrigir layout quebrado na atualização AJAX (coluna Filtros sumindo) - usar 'ufs' em vez de 'estados'
- [x] Exibir Estados na coluna Filtros (campo 'estados' não está sendo mostrado) - campo correto é 'ufs'
- [x] Formatar CNAEs separados por vírgula (não em JSON) - parsear JSON e exibir separado por vírgula
- [x] Corrigir erro 404 ao pausar tarefa (adicionar rota) - rotas adicionadas no Routes.php
- [x] Adicionar botão "Iniciar" (play) para tarefas não em andamento
- [x] Botão Iniciar deve pausar outras tarefas e iniciar a clicada
- [x] Adicionar botão "Reiniciar" (reload) que reseta status e apaga arquivo de progresso
- [x] Modificar botão "Clonar" para redirecionar ao formulário com dados preenchidos


## 🚨 PROBLEMAS CRÍTICOS - PRIORIDADE MÁXIMA

### Sistema de Bounces/Complaints (AWS SNS)
- [x] Investigar por que bounces não estão sendo registrados na aplicação - CRON não configurado
- [x] Verificar configuração do endpoint SNS - Implementação correta, falta executar
- [x] Verificar processamento de notificações SNS (QueueController) - Funcional
- [ ] **URGENTE: Configurar CRON para /queue/process-bounces (a cada 5 minutos)**
- [x] Implementar logging detalhado de bounces - Logs adicionados
- [ ] Testar recebimento de bounces/complaints após CRON configurado
- [ ] Sincronizar lista de supressão da AWS com aplicação

### Opt-out
- [x] Revisar funcionamento completo do opt-out - Funcionando corretamente
- [x] Verificar se contatos opt-out são excluídos dos envios - Sim, filtrados no QueueManager
- [x] Testar fluxo completo de opt-out - Complaints viram opt-out automaticamente

### Interface de Tarefas
- [x] Unificar HTML (usar mesma view para primeira requisição e AJAX) - Partial _task_row.php criada
- [x] Corrigir botão Clonar (erro 404 em duplicate-task/1:1) - Validação de ID adicionada
- [x] Reorganizar lógica dos botões:
  - [x] Garantir que apenas 1 tarefa rode por vez - startTask pausa outras
  - [x] Botão Reload sempre visível - Implementado
  - [x] Lógica coerente entre Play/Pause/Reload - Reorganizado
  - [x] Ao reiniciar: zerar colunas de progresso e apagar arquivo - restartTask completo


## Novos Problemas Reportados

- [x] Botão Clonar não preenche formulário (sessionStorage não carrega dados) - Timeout adicionado + parsing flexível
- [x] Reiniciar tarefa: progresso volta após CRON (arquivo não apagado ou campos não resetados corretamente) - Validação adicionada (não reiniciar em andamento)
- [x] Filtro de situações fiscais usa coluna errada (deve ser coluna 6, 2 dígitos com zero: 01, 02, 03, 04, 08) - Normalização com str_pad


## Problema: Contagem de Registros Importados Incorreta (NOVAMENTE)

- [x] Interface mostra 264 registros importados
- [x] Banco de dados tem 33.549 registros
- [x] Investigar por que affectedRows() não funcionou - affectedRows() ignora duplicatas
- [x] Corrigir lógica de contagem definitivamente - usar count($batchData) em vez de affectedRows()


## Nova Regra: Contar Apenas Estabelecimentos

- [x] Modificar contagem de imported_lines para somar apenas arquivo "estabelecimentos"
- [x] Ignorar contagem de empresas, socios, simples, etc.


## Problema: imported_lines sendo sobrescrito em vez de somado

- [x] A cada CRON, imported_lines é sobrescrito com valor da execução atual
- [x] Deve somar ao valor já existente no banco (incremento)
- [x] Modificar UPDATE para usar SQL direto: SET imported_lines = imported_lines + X


## Novas Funcionalidades - Listagem de Empresas Importadas

- [x] Adicionar checkbox "Somente com e-mail" no filtro
- [x] Adicionar checkbox "Somente com telefone" no filtro
- [x] **CORRIGIDO:** Filtros de e-mail e telefone - nomes de colunas corrigidos (ddd1, telefone1, ddd2, telefone2)
- [x] Formatar CNPJ para 00.000.000/0000-00
- [x] Adicionar opção para criar lista de contatos a partir das empresas filtradas (já estava implementada)


## Correções: Sistema de Listas de Contatos (Solicitação Original)

- [x] Remover campo "Criar Lista de Contatos" do formulário de importação
- [x] Remover checkbox "Adicionar Contatos de Contabilidade" do formulário de importação
- [x] Remover campos do JavaScript (receita-index.js)
- [x] Verificar/corrigir card na view /receita/empresas - Adicionado checkbox de contabilidade
- [x] Garantir que card aparece quando há filtros ativos - Já estava implementado
- [x] Implementar Select2 com tagging para criar novas listas - Já estava implementado
- [x] Corrigir filtro de e-mail (está listando empresas com e-mail em branco) - Usando TRIM e validação robusta
- [x] Corrigir importação para salvar NULL em vez de "" no campo correio_eletronico


## Nova Solicitação: Converter Todos os Campos Vazios em NULL

- [x] Aplicar lógica de conversão de strings vazias para NULL em **todos os campos** durante importação
- [x] Não apenas correio_eletronico, mas todos os campos da tabela


## Problema: Card de Adição à Lista Não Aparece

- [x] Investigar por que o card não aparece quando há filtros ativos
- [x] Verificar lógica de exibição no empresas.php
- [x] Verificar JavaScript que controla visibilidade do card
- [x] Corrigir condição de exibição
- [x] Corrigir nomes de colunas no filtro de telefone (ddd1 → ddd_telefone_1, etc)
- [x] Adicionar razao_social ao SELECT
- [x] Trocar slideDown/slideUp por show/hide para exibição instantânea
- [x] Adicionar console.log para debug


## Novos Problemas Reportados

- [x] Erro SQL: Unknown column 'razao_social' - verificar nome correto da coluna (removido do SELECT)
- [x] Implementar busca automática por GET (quando URL tem parâmetros de filtro)
- [x] Card deve aparecer automaticamente quando há parâmetros GET com filtros ativos
- [x] Criar função loadFiltersFromURL() para popular formulário com parâmetros GET
- [x] Executar busca automaticamente ao carregar página com parâmetros GET


## URGENTE: Nomes de Colunas Incorretos

- [x] Estrutura real da tabela: ddd1, telefone1, ddd2, telefone2 (sem underscores)
- [x] Corrigir controller: ddd_telefone_1 → ddd1, telefone_1 → telefone1, etc
- [x] Corrigir JavaScript: ddd_telefone_1 → ddd1, etc
- [x] Corrigir filtro de telefone no controller
- [x] Corrigir SELECT no controller


## Problema: Busca Não Atualiza URL com Parâmetros GET

- [x] Formulário está fazendo AJAX sem atualizar URL
- [x] Modificar para usar history.pushState() e atualizar URL com parâmetros
- [x] Permitir compartilhamento de URLs com filtros aplicados
- [x] Criar função updateURLWithFilters()
- [x] Chamar updateURLWithFilters() em buscarEmpresas()


## Problema: Select2 de Listas de Contatos Não Funciona

- [x] Select2 não está exibindo listas existentes
- [x] Tagging não está permitindo criar novas listas
- [x] Verificar endpoint buscarListasContatos (estava correto)
- [x] Verificar configuração do Select2 (AJAX + tags)
- [x] Adicionar minimumInputLength: 0 para permitir busca ao clicar
- [x] Adicionar tokenSeparators para separar por vírgula
- [x] Melhorar mensagens de language
- [x] Garantir que params.term || '' não seja undefined


## URGENTE: Rota buscarListasContatos Não Existe (404)

- [x] Adicionar rota GET /receita/buscarListasContatos ao Routes.php
- [x] Verificar outras rotas faltantes relacionadas
- [x] Adicionar também rota POST /receita/adicionarEmpresasALista
