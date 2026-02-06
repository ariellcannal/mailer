# TODO - Política de Edição de Mensagens e Reenvios

## 📋 Requisitos

### 1. Política de Edição de Mensagens

#### 1.1 Mensagem Agendada (scheduled)
- [ ] Bloquear edição quando status = 'scheduled'
- [ ] Se primeiro envio < 1 minuto: mostrar prompt de confirmação
- [ ] Prompt deve perguntar se deseja transformar em rascunho
- [ ] Se confirmado: alterar status para 'draft' e permitir edição

#### 1.2 Primeiro Envio Já Passou
- [ ] Permitir edição APENAS de:
  - [ ] Horários de reenvio (resend_rules.scheduled_at)
  - [ ] Assuntos de reenvio (resend_rules.subject_override)
- [ ] Bloquear edição de todos os outros campos
- [ ] Validação JavaScript nos campos bloqueados
- [ ] Validação PHP no backend para campos bloqueados

#### 1.3 Todos os Envios Passados
- [ ] Ocultar botão "Editar" completamente
- [ ] Verificar: primeiro envio + 3 reenvios todos no passado

### 2. Validação de Datas

#### 2.1 Integração Tempus Dominus V6
- [ ] Instalar/incluir Tempus Dominus V6 (https://getdatepicker.com/6)
- [ ] Configurar localização conforme CI4 (pt-BR)
- [ ] Aplicar em todos os campos de data/hora de envio e reenvio

#### 2.2 Validação de Data Mínima
- [ ] JavaScript: data/hora mínima = agora + 10 minutos
- [ ] Usar horário do navegador do usuário
- [ ] Aplicar em:
  - [ ] Campo de agendamento do primeiro envio
  - [ ] Campos de agendamento dos 3 reenvios
- [ ] PHP: validação backend da mesma regra

### 3. Lógica de Processamento de Reenvios

#### 3.1 Ordenação e Seleção
- [ ] Buscar todos reenvios pendentes de cada mensagem
- [ ] Ordenar em ordem DECRESCENTE de data (mais recente primeiro)
- [ ] Processar APENAS o reenvio mais próximo de agora
- [ ] Ignorar reenvios com data anterior ao mais recente

#### 3.2 Regras de Processamento
- [ ] Reenvios com data < reenvio mais recente: não enviar
- [ ] Apenas enviar se usuário alterar manualmente a data
- [ ] Marcar reenvios ignorados com status específico (opcional)

## 🔧 Arquivos a Modificar

### Backend (PHP)
- [ ] `app/Controllers/MessageController.php`
  - [ ] Método `edit()`: adicionar lógica de bloqueio
  - [ ] Método `update()`: validar campos permitidos
  - [ ] Novo método: `canEdit()` - verificar permissões de edição
  - [ ] Novo método: `getEditableFields()` - retornar campos editáveis

- [ ] `app/Libraries/Email/QueueManager.php`
  - [ ] Método `queueResendsDue()`: implementar nova lógica de ordenação
  - [ ] Adicionar lógica para ignorar reenvios antigos

- [ ] `app/Models/MessageModel.php`
  - [ ] Adicionar método `canEdit($messageId)`
  - [ ] Adicionar método `getEditPermissions($messageId)`

### Frontend (JavaScript/Views)
- [ ] `app/Views/messages/edit.php`
  - [ ] Integrar Tempus Dominus V6
  - [ ] Adicionar validação de data mínima (+10 min)
  - [ ] Bloquear campos conforme regras
  - [ ] Adicionar prompt de confirmação para rascunho

- [ ] `app/Views/messages/list.php` (ou similar)
  - [ ] Ocultar botão "Editar" quando todos envios passaram

- [ ] `public/assets/js/message-edit.js` (criar se não existir)
  - [ ] Validações JavaScript
  - [ ] Integração com Tempus Dominus
  - [ ] Lógica de bloqueio de campos

### Assets
- [ ] Incluir Tempus Dominus V6 CSS
- [ ] Incluir Tempus Dominus V6 JS
- [ ] Incluir locale pt-BR do Tempus Dominus

## 📝 Fluxo de Implementação

1. ✅ Analisar código existente
2. ⏳ Implementar backend (validações PHP)
3. ⏳ Implementar frontend (validações JS + Tempus Dominus)
4. ⏳ Corrigir lógica de reenvios
5. ⏳ Testes completos
6. ⏳ Documentação e commit

## 🧪 Cenários de Teste

### Teste 1: Edição de Mensagem Agendada
- Criar mensagem agendada para daqui a 5 minutos
- Tentar editar: deve bloquear
- Criar mensagem agendada para daqui a 30 segundos
- Tentar editar: deve mostrar prompt
- Confirmar prompt: deve transformar em rascunho

### Teste 2: Edição Após Primeiro Envio
- Criar mensagem com primeiro envio no passado
- Tentar editar: deve permitir apenas reenvios
- Tentar alterar assunto principal: deve bloquear
- Alterar horário de reenvio: deve permitir

### Teste 3: Mensagem Totalmente Enviada
- Criar mensagem com todos envios no passado
- Botão "Editar" não deve aparecer

### Teste 4: Validação de Data
- Tentar agendar para daqui a 5 minutos: deve bloquear
- Tentar agendar para daqui a 15 minutos: deve permitir

### Teste 5: Processamento de Reenvios
- Criar mensagem com 3 reenvios:
  - Reenvio 1: daqui a 1 hora
  - Reenvio 2: daqui a 30 minutos
  - Reenvio 3: daqui a 2 horas
- Processar fila: deve enviar apenas reenvio 2 (mais próximo)
- Reenvios 1 e 3 devem ser ignorados

---

**Data de criação:** 28/01/2026
**Status:** Em desenvolvimento
