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
