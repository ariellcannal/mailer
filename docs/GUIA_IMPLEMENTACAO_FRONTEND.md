# Guia de Implementação Frontend - Tempus Dominus V6 e Validações

## 📋 Resumo

Este guia contém todas as instruções para implementar:
1. Integração do Tempus Dominus V6 para seleção de datas
2. Validações JavaScript de data mínima (+10 minutos)
3. Bloqueio de campos baseado em permissões de edição
4. Prompt de confirmação para transformar mensagem em rascunho

---

## 🔧 Parte 1: Instalação do Tempus Dominus V6

### 1.1 Adicionar CSS e JS no layout principal

Edite o arquivo `app/Views/layouts/main.php` (ou equivalente) e adicione no `<head>`:

```html
<!-- Tempus Dominus V6 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.9.4/dist/css/tempus-dominus.min.css" crossorigin="anonymous">

<!-- Font Awesome (requerido pelo Tempus Dominus) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

Antes do fechamento do `</body>`:

```html
<!-- Popper.js (requerido pelo Tempus Dominus) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

<!-- Tempus Dominus V6 JS -->
<script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.9.4/dist/js/tempus-dominus.min.js" crossorigin="anonymous"></script>

<!-- Locale pt-BR -->
<script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.9.4/dist/locales/pt-BR.js" crossorigin="anonymous"></script>
```

---

## 🔧 Parte 2: Criar JavaScript para Controle de Edição

### 2.1 Criar arquivo `public/assets/js/message-edit.js`

```javascript
/**
 * Controle de Edição de Mensagens
 * Gerencia permissões, validações e Tempus Dominus
 */

const MessageEdit = {
    /**
     * Configuração do Tempus Dominus
     */
    tempusDominusConfig: {
        localization: {
            locale: 'pt-BR',
            format: 'dd/MM/yyyy HH:mm'
        },
        display: {
            theme: 'light',
            components: {
                calendar: true,
                date: true,
                month: true,
                year: true,
                decades: true,
                clock: true,
                hours: true,
                minutes: true,
                seconds: false
            },
            buttons: {
                today: true,
                clear: true,
                close: true
            }
        },
        restrictions: {
            minDate: null // Será definido dinamicamente
        }
    },

    /**
     * Inicializa o controle de edição
     */
    init: function(editPermissions) {
        this.editPermissions = editPermissions || {};
        
        // Aplicar bloqueios de campos
        this.applyFieldLocks();
        
        // Inicializar date pickers
        this.initDatePickers();
        
        // Mostrar prompt de rascunho se necessário
        if (this.editPermissions.show_draft_prompt) {
            this.showDraftPrompt();
        }
        
        // Adicionar validações de formulário
        this.attachFormValidation();
    },

    /**
     * Aplica bloqueios de campos baseado em permissões
     */
    applyFieldLocks: function() {
        const editMode = this.editPermissions.edit_mode;
        
        if (editMode === 'resend_only') {
            // Bloquear todos os campos exceto reenvios
            this.lockField('#campaign_id', 'Não é possível alterar a campanha após o primeiro envio');
            this.lockField('#sender_id', 'Não é possível alterar o remetente após o primeiro envio');
            this.lockField('#subject', 'Não é possível alterar o assunto principal após o primeiro envio');
            this.lockField('#from_name', 'Não é possível alterar o nome do remetente após o primeiro envio');
            this.lockField('#reply_to', 'Não é possível alterar o reply-to após o primeiro envio');
            this.lockField('#scheduled_at', 'Não é possível alterar a data do primeiro envio');
            
            // Bloquear editor GrapesJS
            if (typeof window.grapesjsEditor !== 'undefined') {
                window.grapesjsEditor.setComponents(window.grapesjsEditor.getHtml());
                window.grapesjsEditor.getModel().set('dmode', 'absolute');
            }
            
            // Bloquear listas de contatos
            $('input[name="contact_lists[]"]').prop('disabled', true);
            
            // Mostrar aviso
            this.showWarning('Apenas os horários e assuntos dos reenvios podem ser editados após o primeiro envio.');
            
        } else if (editMode === 'none') {
            // Bloquear tudo
            $('input, select, textarea, button[type="submit"]').prop('disabled', true);
            this.showWarning(this.editPermissions.reason || 'Esta mensagem não pode ser editada.');
        }
    },

    /**
     * Bloqueia um campo específico
     */
    lockField: function(selector, reason) {
        const $field = $(selector);
        $field.prop('disabled', true);
        $field.prop('readonly', true);
        $field.addClass('field-locked');
        
        // Adicionar tooltip com motivo
        $field.attr('title', reason);
        $field.attr('data-toggle', 'tooltip');
    },

    /**
     * Inicializa date pickers com Tempus Dominus
     */
    initDatePickers: function() {
        const self = this;
        
        // Data mínima: agora + 10 minutos
        const minDate = new Date();
        minDate.setMinutes(minDate.getMinutes() + 10);
        
        // Configurar date picker para primeiro envio
        const $scheduledAt = document.getElementById('scheduled_at');
        if ($scheduledAt && !$scheduledAt.disabled) {
            const config = { ...this.tempusDominusConfig };
            config.restrictions.minDate = minDate;
            
            const picker = new tempusDominus.TempusDominus($scheduledAt, config);
            
            // Validação adicional
            $scheduledAt.addEventListener('change.td', function(e) {
                self.validateDateTime(e.detail.date, 'Data do primeiro envio');
            });
        }
        
        // Configurar date pickers para reenvios
        $('[id^="resend_scheduled_"]').each(function() {
            if (!this.disabled) {
                const config = { ...self.tempusDominusConfig };
                config.restrictions.minDate = minDate;
                
                const picker = new tempusDominus.TempusDominus(this, config);
                
                // Validação adicional
                this.addEventListener('change.td', function(e) {
                    self.validateDateTime(e.detail.date, 'Data do reenvio');
                });
            }
        });
    },

    /**
     * Valida data/hora selecionada
     */
    validateDateTime: function(selectedDate, fieldName) {
        if (!selectedDate) return true;
        
        const now = new Date();
        const minDate = new Date(now.getTime() + 10 * 60000); // +10 minutos
        
        if (selectedDate < minDate) {
            alertify.error(fieldName + ' deve ser pelo menos 10 minutos no futuro');
            return false;
        }
        
        return true;
    },

    /**
     * Mostra prompt para transformar em rascunho
     */
    showDraftPrompt: function() {
        const messageId = this.getMessageId();
        const timeUntilSend = this.editPermissions.time_until_send || 0;
        
        alertify.confirm(
            'Transformar em Rascunho?',
            `Esta mensagem está agendada para envio em menos de 1 minuto (${timeUntilSend}s). 
            Deseja transformá-la em rascunho para poder editá-la?`,
            function() {
                // Confirmou: transformar em rascunho
                $.post('/messages/convert-to-draft/' + messageId, function(response) {
                    alertify.success('Mensagem transformada em rascunho');
                    window.location.reload();
                }).fail(function() {
                    alertify.error('Erro ao transformar mensagem em rascunho');
                });
            },
            function() {
                // Cancelou: redirecionar para visualização
                window.location.href = '/messages/view/' + messageId;
            }
        );
    },

    /**
     * Adiciona validações ao formulário
     */
    attachFormValidation: function() {
        const self = this;
        
        $('form[action*="/messages/update"]').on('submit', function(e) {
            // Validar datas antes de enviar
            let valid = true;
            
            // Validar data do primeiro envio
            const scheduledAt = $('#scheduled_at').val();
            if (scheduledAt && !self.validateDateTimeString(scheduledAt)) {
                alertify.error('Data do primeiro envio deve ser pelo menos 10 minutos no futuro');
                valid = false;
            }
            
            // Validar datas de reenvio
            $('[id^="resend_scheduled_"]').each(function() {
                const value = $(this).val();
                if (value && !self.validateDateTimeString(value)) {
                    alertify.error('Data do reenvio deve ser pelo menos 10 minutos no futuro');
                    valid = false;
                    return false; // break
                }
            });
            
            if (!valid) {
                e.preventDefault();
                return false;
            }
        });
    },

    /**
     * Valida string de data/hora
     */
    validateDateTimeString: function(dateTimeStr) {
        if (!dateTimeStr) return true;
        
        // Converter string para Date
        const parts = dateTimeStr.match(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2})/);
        if (!parts) return true; // Formato inválido, deixar backend validar
        
        const selectedDate = new Date(parts[3], parts[2] - 1, parts[1], parts[4], parts[5]);
        const now = new Date();
        const minDate = new Date(now.getTime() + 10 * 60000);
        
        return selectedDate >= minDate;
    },

    /**
     * Mostra aviso na tela
     */
    showWarning: function(message) {
        const html = `
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        
        $('.page-header').after(html);
    },

    /**
     * Obtém ID da mensagem da URL
     */
    getMessageId: function() {
        const match = window.location.pathname.match(/\/messages\/(edit|view)\/(\d+)/);
        return match ? match[2] : null;
    }
};

// Exportar para uso global
window.MessageEdit = MessageEdit;
```

---

## 🔧 Parte 3: Modificar View de Edição

### 3.1 Editar `app/Views/messages/detail.php`

Adicione no final do arquivo, antes do fechamento do `</body>` ou na seção de scripts:

```php
<!-- Scripts de controle de edição -->
<script src="<?= base_url('assets/js/message-edit.js') ?>"></script>

<script>
$(document).ready(function() {
    // Inicializar controle de edição
    <?php if (isset($editPermissions)): ?>
    const editPermissions = <?= json_encode($editPermissions) ?>;
    MessageEdit.init(editPermissions);
    <?php else: ?>
    // Modo criação: apenas inicializar date pickers
    MessageEdit.init({
        edit_mode: 'full',
        can_edit: true,
        show_draft_prompt: false
    });
    <?php endif; ?>
    
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
```

### 3.2 Adicionar CSS para campos bloqueados

Adicione no arquivo CSS principal (ou crie `public/assets/css/message-edit.css`):

```css
/* Campos bloqueados */
.field-locked {
    background-color: #f5f5f5 !important;
    cursor: not-allowed !important;
    opacity: 0.7;
}

.field-locked:hover {
    background-color: #ececec !important;
}

/* Aviso de edição restrita */
.alert-warning {
    margin-top: 20px;
    margin-bottom: 20px;
}

/* Tempus Dominus customização */
.tempus-dominus-widget {
    z-index: 9999 !important;
}
```

---

## 🔧 Parte 4: Modificar View de Listagem

### 4.1 Editar `app/Views/messages/index.php`

Adicione lógica para ocultar botão "Editar" quando necessário:

```php
<?php foreach ($messages as $message): ?>
    <tr>
        <!-- ... outras colunas ... -->
        <td>
            <?php
            // Verificar se pode mostrar botão editar
            $canShowEdit = true;
            
            // Se mensagem tem scheduled_at
            if (!empty($message['scheduled_at'])) {
                $scheduledTime = strtotime($message['scheduled_at']);
                $now = time();
                
                // Se primeiro envio já passou
                if ($scheduledTime < $now) {
                    // Verificar se todos os reenvios também passaram
                    $db = \Config\Database::connect();
                    $futureResends = $db->table('resend_rules')
                        ->where('message_id', $message['id'])
                        ->where('scheduled_at >=', date('Y-m-d H:i:s'))
                        ->countAllResults();
                    
                    // Se não há reenvios futuros, ocultar botão editar
                    if ($futureResends === 0) {
                        $canShowEdit = false;
                    }
                }
            }
            ?>
            
            <a href="<?= base_url('messages/view/' . $message['id']) ?>" class="btn btn-sm btn-info">
                <i class="fas fa-eye"></i> Ver
            </a>
            
            <?php if ($canShowEdit): ?>
            <a href="<?= base_url('messages/edit/' . $message['id']) ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
            <?php endif; ?>
            
            <!-- ... outros botões ... -->
        </td>
    </tr>
<?php endforeach; ?>
```

---

## 🔧 Parte 5: Atualizar Schema do Banco

### 5.1 Adicionar coluna `status` na tabela `resend_rules`

Se ainda não existe, execute:

```sql
ALTER TABLE resend_rules 
MODIFY COLUMN status ENUM('pending', 'completed', 'cancelled', 'skipped') DEFAULT 'pending';
```

---

## 🧪 Testes

### Teste 1: Mensagem Agendada (> 1 minuto)
1. Criar mensagem agendada para daqui a 5 minutos
2. Tentar editar
3. **Esperado:** Botão "Editar" não aparece ou redireciona com erro

### Teste 2: Mensagem Agendada (< 1 minuto)
1. Criar mensagem agendada para daqui a 30 segundos
2. Clicar em "Editar"
3. **Esperado:** Prompt perguntando se deseja transformar em rascunho
4. Confirmar
5. **Esperado:** Mensagem vira rascunho e pode ser editada

### Teste 3: Primeiro Envio Passou
1. Criar mensagem com primeiro envio no passado e reenvios futuros
2. Editar mensagem
3. **Esperado:** Apenas campos de reenvio editáveis
4. Tentar alterar assunto principal
5. **Esperado:** Campo bloqueado

### Teste 4: Validação de Data (+10 min)
1. Tentar agendar mensagem para daqui a 5 minutos
2. **Esperado:** Erro "Data deve ser pelo menos 10 minutos no futuro"

### Teste 5: Todos Envios Passados
1. Criar mensagem com todos envios no passado
2. **Esperado:** Botão "Editar" não aparece na listagem

### Teste 6: Processamento de Reenvios
1. Criar mensagem com 3 reenvios:
   - Reenvio 1: 10h
   - Reenvio 2: 11h (mais recente)
   - Reenvio 3: 09h
2. Processar fila quando horário atual = 11h05
3. **Esperado:** Apenas reenvio 2 é processado
4. **Esperado:** Reenvios 1 e 3 marcados como 'skipped'

---

## 📝 Checklist de Implementação

- [ ] Adicionar Tempus Dominus V6 CSS/JS no layout
- [ ] Criar arquivo `message-edit.js`
- [ ] Adicionar CSS para campos bloqueados
- [ ] Modificar `messages/detail.php` para incluir scripts
- [ ] Modificar `messages/index.php` para ocultar botão editar
- [ ] Atualizar schema do banco (coluna `status` em `resend_rules`)
- [ ] Testar todos os cenários
- [ ] Verificar logs de processamento de reenvios

---

## 🐛 Troubleshooting

### Tempus Dominus não aparece
- Verificar se jQuery está carregado antes
- Verificar se Popper.js está carregado
- Verificar console do navegador para erros

### Campos não estão bloqueando
- Verificar se `editPermissions` está sendo passado corretamente
- Verificar console do navegador
- Verificar se `message-edit.js` está sendo carregado

### Validação de data não funciona
- Verificar formato de data (dd/MM/yyyy HH:mm)
- Verificar timezone do navegador vs servidor
- Adicionar logs no JavaScript para debug

---

**Data:** 28/01/2026  
**Status:** Pronto para implementação
