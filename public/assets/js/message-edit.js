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
            format: 'dd/MM/yyyy HH:mm',
            dayViewHeaderFormat: { month: 'long', year: 'numeric' },
            startOfTheWeek: 0 // Domingo
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
        console.log('MessageEdit.init() chamado', editPermissions);
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
        console.log('Aplicando bloqueios, modo:', editMode);
        
        if (editMode === 'resend_only') {
            // Bloquear todos os campos exceto reenvios
            this.lockField('#campaign_id', 'Não é possível alterar a campanha após o primeiro envio');
            this.lockField('#sender_id', 'Não é possível alterar o remetente após o primeiro envio');
            this.lockField('#subject', 'Não é possível alterar o assunto principal após o primeiro envio');
            this.lockField('#from_name', 'Não é possível alterar o nome do remetente após o primeiro envio');
            this.lockField('#reply_to', 'Não é possível alterar o reply-to após o primeiro envio');
            this.lockField('#scheduled_at', 'Não é possível alterar a data do primeiro envio');
            
            // Bloquear editor GrapesJS se existir
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
        if ($field.length) {
            $field.prop('disabled', true);
            $field.prop('readonly', true);
            $field.addClass('field-locked');
            
            // Adicionar tooltip com motivo
            $field.attr('title', reason);
            $field.attr('data-toggle', 'tooltip');
        }
    },

    /**
     * Inicializa date pickers com Tempus Dominus
     */
    initDatePickers: function() {
        console.log('Inicializando date pickers...');
        const self = this;
        
        // Data mínima: agora + 10 minutos
        const minDate = new Date();
        minDate.setMinutes(minDate.getMinutes() + 10);
        
        // Configurar date picker para primeiro envio
        const scheduledAtInput = document.getElementById('scheduled_at');
        if (scheduledAtInput && !scheduledAtInput.disabled) {
            console.log('Inicializando Tempus Dominus para #scheduled_at');
            try {
                const config = { ...this.tempusDominusConfig };
                config.restrictions.minDate = minDate;
                
                new tempusDominus.TempusDominus(scheduledAtInput, config);
                console.log('Tempus Dominus inicializado com sucesso para #scheduled_at');
            } catch (e) {
                console.error('Erro ao inicializar Tempus Dominus:', e);
            }
        }
        
        // Configurar date pickers para reenvios
        document.querySelectorAll('[id^="resend_scheduled_"]').forEach(function(input) {
            if (!input.disabled) {
                console.log('Inicializando Tempus Dominus para', input.id);
                try {
                    const config = { ...self.tempusDominusConfig };
                    config.restrictions.minDate = minDate;
                    
                    new tempusDominus.TempusDominus(input, config);
                    console.log('Tempus Dominus inicializado com sucesso para', input.id);
                } catch (e) {
                    console.error('Erro ao inicializar Tempus Dominus para', input.id, e);
                }
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
            if (typeof alertify !== 'undefined') {
                alertify.error(fieldName + ' deve ser pelo menos 10 minutos no futuro');
            } else {
                alert(fieldName + ' deve ser pelo menos 10 minutos no futuro');
            }
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
        
        const confirmMsg = `Esta mensagem está agendada para envio em menos de 1 minuto (${timeUntilSend}s). Deseja transformá-la em rascunho para poder editá-la?`;
        
        if (typeof alertify !== 'undefined') {
            alertify.confirm(
                'Transformar em Rascunho?',
                confirmMsg,
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
        } else {
            if (confirm(confirmMsg)) {
                $.post('/messages/convert-to-draft/' + messageId, function(response) {
                    alert('Mensagem transformada em rascunho');
                    window.location.reload();
                }).fail(function() {
                    alert('Erro ao transformar mensagem em rascunho');
                });
            } else {
                window.location.href = '/messages/view/' + messageId;
            }
        }
    },

    /**
     * Adiciona validações ao formulário
     */
    attachFormValidation: function() {
        const self = this;
        
        $('form[action*="/messages/update"], form[action*="/messages/store"]').on('submit', function(e) {
            // Validar datas antes de enviar
            let valid = true;
            
            // Validar data do primeiro envio
            const scheduledAt = $('#scheduled_at').val();
            if (scheduledAt && !self.validateDateTimeString(scheduledAt)) {
                if (typeof alertify !== 'undefined') {
                    alertify.error('Data do primeiro envio deve ser pelo menos 10 minutos no futuro');
                } else {
                    alert('Data do primeiro envio deve ser pelo menos 10 minutos no futuro');
                }
                valid = false;
            }
            
            // Validar datas de reenvio
            $('[id^="resend_scheduled_"]').each(function() {
                const value = $(this).val();
                if (value && !self.validateDateTimeString(value)) {
                    if (typeof alertify !== 'undefined') {
                        alertify.error('Data do reenvio deve ser pelo menos 10 minutos no futuro');
                    } else {
                        alert('Data do reenvio deve ser pelo menos 10 minutos no futuro');
                    }
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
        
        // Converter string para Date (formato: dd/MM/yyyy HH:mm)
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
        
        if ($('.page-header').length) {
            $('.page-header').after(html);
        } else if ($('.content-header').length) {
            $('.content-header').after(html);
        } else {
            $('body').prepend(html);
        }
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

// Log para debug
console.log('message-edit.js carregado com sucesso');
