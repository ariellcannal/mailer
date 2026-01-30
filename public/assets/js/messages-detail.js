/**
 * Messages Detail Page
 * Inicializa MessageEdit com permissões
 */
(function() {
    'use strict';

    $(document).ready(function() {
        console.log('Inicializando MessageEdit...');
        
        // Verificar se MessageEdit está disponível
        if (typeof MessageEdit === 'undefined') {
            console.error('MessageEdit não está disponível');
            return;
        }
        
        // Buscar permissões do elemento data attribute
        const permissionsElement = document.getElementById('edit-permissions-data');
        
        if (permissionsElement) {
            // Modo edição: usar permissões do backend
            try {
                const editPermissions = JSON.parse(permissionsElement.textContent);
                console.log('Edit permissions:', editPermissions);
                MessageEdit.init(editPermissions);
            } catch (e) {
                console.error('Erro ao parsear permissões:', e);
            }
        } else {
            // Modo criação: permissões completas
            console.log('Modo criação');
            MessageEdit.init({
                edit_mode: 'full',
                can_edit: true,
                show_draft_prompt: false
            });
        }
        
        // Inicializar tooltips Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
})();

/**
 * Gerenciamento de Reenvios (Modal)
 */
const ResendManager = {
    resends: [],
    modal: null,
    editingIndex: null,
    
    /**
     * Inicializa o gerenciador de reenvios
     */
    init: function() {
        this.modal = new bootstrap.Modal(document.getElementById('resendModal'));
        this.attachEvents();
        this.loadExistingResends();
        this.renderTable();
    },
    
    /**
     * Carrega reenvios existentes do PHP
     */
    loadExistingResends: function() {
        // Tentar carregar dados do PHP (se estiver editando)
        const resendRulesElement = document.getElementById('resend-rules-data');
        if (resendRulesElement) {
            try {
                const rules = JSON.parse(resendRulesElement.textContent);
                rules.forEach(rule => {
                    this.resends.push({
                        number: parseInt(rule.resend_number),
                        scheduled_at: rule.scheduled_at,
                        subject: rule.subject_override || rule.subject || ''
                    });
                });
            } catch (e) {
                console.error('Erro ao carregar reenvios:', e);
            }
        }
    },
    
    /**
     * Anexa eventos aos botões
     */
    attachEvents: function() {
        const self = this;
        
        // Botão adicionar reenvio
        $('#btnAddResend').on('click', function() {
            self.openModal();
        });
        
        // Botão salvar no modal
        $('#btnSaveResend').on('click', function() {
            self.saveResend();
        });
        
        // Limpar formulário ao fechar modal
        $('#resendModal').on('hidden.bs.modal', function() {
            self.resetForm();
        });
        
        // Inicializar datepicker no campo do modal
        this.initDatePicker();
    },
    
    /**
     * Inicializa datepicker no campo de data/hora
     */
    initDatePicker: function() {
        const input = document.getElementById('resendScheduledAt');
        if (!input) return;
        
        new tempusDominus.TempusDominus(input, {
            display: {
				sideBySide: true,
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
                icons: {
                    time: 'fas fa-clock',
                    date: 'fas fa-calendar',
                    up: 'fas fa-arrow-up',
                    down: 'fas fa-arrow-down',
                    previous: 'fas fa-chevron-left',
                    next: 'fas fa-chevron-right',
                    today: 'fas fa-calendar-check',
                    clear: 'fas fa-trash',
                    close: 'fas fa-times'
                }
            },
            localization: {
                locale: 'pt-BR',
                format: 'dd/MM/yyyy HH:mm',
                dayViewHeaderFormat: { month: 'long', year: 'numeric' }
            },
            restrictions: {
                minDate: new Date(Date.now() + 10 * 60 * 1000) // Mínimo 10 minutos
            }
        });
    },
    
    /**
     * Abre modal para adicionar/editar
     */
    openModal: function(index = null) {
        this.editingIndex = index;
        
        if (index !== null && this.resends[index]) {
            // Modo edição
            const resend = this.resends[index];
            $('#resendModalLabel').text('Editar Reenvio');
            $('#resendNumber').val(resend.number).prop('disabled', true);
            $('#resendScheduledAt').val(this.formatDateForDisplay(resend.scheduled_at));
            $('#resendSubject').val(resend.subject);
        } else {
            // Modo adição
            $('#resendModalLabel').text('Adicionar Reenvio');
            $('#resendNumber').prop('disabled', false);
            this.updateAvailableNumbers();
        }
        
        this.modal.show();
    },
    
    /**
     * Atualiza números disponíveis no select
     */
    updateAvailableNumbers: function() {
        const usedNumbers = this.resends.map(r => r.number);
        $('#resendNumber option').each(function() {
            const val = parseInt($(this).val());
            if (val && usedNumbers.includes(val)) {
                $(this).prop('disabled', true);
            } else {
                $(this).prop('disabled', false);
            }
        });
    },
    
    /**
     * Salva reenvio (adicionar ou editar)
     */
    saveResend: function() {
        const number = parseInt($('#resendNumber').val());
        const scheduled_at = $('#resendScheduledAt').val();
        const subject = $('#resendSubject').val().trim();
        
        // Validações
        if (!number || !scheduled_at || !subject) {
            alertify.error('Preencha todos os campos');
            return;
        }
        
        // Converter data para formato ISO
        const isoDate = this.convertToISO(scheduled_at);
        if (!isoDate) {
            alertify.error('Data/hora inválida');
            return;
        }
        
        const resendData = {
            number: number,
            scheduled_at: isoDate,
            subject: subject
        };
        
        if (this.editingIndex !== null) {
            // Editar existente
            this.resends[this.editingIndex] = resendData;
        } else {
            // Adicionar novo
            this.resends.push(resendData);
        }
        
        // Ordenar por número
        this.resends.sort((a, b) => a.number - b.number);
        
        this.renderTable();
        this.updateHiddenInputs();
        this.modal.hide();
        
        alertify.success('Reenvio salvo com sucesso');
    },
    
    /**
     * Remove reenvio
     */
    removeResend: function(index) {
        alertify.confirm(
            'Remover Reenvio',
            'Tem certeza que deseja remover este reenvio?',
            () => {
                this.resends.splice(index, 1);
                this.renderTable();
                this.updateHiddenInputs();
                alertify.success('Reenvio removido');
            },
            () => {}
        );
    },
    
    /**
     * Renderiza tabela de reenvios
     */
    renderTable: function() {
        const tbody = $('#resendsTable tbody');
        tbody.empty();
        
        if (this.resends.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        Nenhum reenvio configurado
                    </td>
                </tr>
            `);
        } else {
            this.resends.forEach((resend, index) => {
                tbody.append(`
                    <tr>
                        <td>Reenvio ${resend.number}</td>
                        <td>${this.formatDateForDisplay(resend.scheduled_at)}</td>
                        <td>${this.escapeHtml(resend.subject)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-primary" onclick="ResendManager.openModal(${index})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="ResendManager.removeResend(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        }
        
        // Atualizar botão de adicionar
        if (this.resends.length >= 3) {
            $('#btnAddResend').prop('disabled', true).attr('title', 'Máximo de 3 reenvios');
        } else {
            $('#btnAddResend').prop('disabled', false).removeAttr('title');
        }
    },
    
    /**
     * Atualiza inputs hidden para envio do formulário
     */
    updateHiddenInputs: function() {
        const container = $('#resendHiddenInputs');
        container.empty();
        
        this.resends.forEach((resend, index) => {
            container.append(`
                <input type="hidden" name="resends[${index}][number]" value="${resend.number}">
                <input type="hidden" name="resends[${index}][scheduled_at]" value="${resend.scheduled_at}">
                <input type="hidden" name="resends[${index}][subject]" value="${this.escapeHtml(resend.subject)}">
            `);
        });
    },
    
    /**
     * Reseta formulário do modal
     */
    resetForm: function() {
        $('#resendForm')[0].reset();
        $('#resendNumber').prop('disabled', false);
        this.editingIndex = null;
    },
    
    /**
     * Converte data dd/mm/yyyy HH:mm para ISO
     */
    convertToISO: function(dateStr) {
        const parts = dateStr.match(/(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})/);
        if (!parts) return null;
        
        const [, day, month, year, hour, minute] = parts;
        return `${year}-${month}-${day} ${hour}:${minute}:00`;
    },
    
    /**
     * Formata data ISO para exibição
     */
    formatDateForDisplay: function(isoDate) {
        if (!isoDate) return '';
        
        const date = new Date(isoDate);
        if (isNaN(date.getTime())) return isoDate;
        
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hour = String(date.getHours()).padStart(2, '0');
        const minute = String(date.getMinutes()).padStart(2, '0');
        
        return `${day}/${month}/${year} ${hour}:${minute}`;
    },
    
    /**
     * Escapa HTML
     */
    escapeHtml: function(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
};

// Inicializar quando documento estiver pronto
$(document).ready(function() {
    // Aguardar um pouco para garantir que outros scripts carregaram
    setTimeout(function() {
        ResendManager.init();
    }, 100);
});
