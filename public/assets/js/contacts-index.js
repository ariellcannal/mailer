(function() {
    let currentAction = null; // 'add_lists', 'export_csv', 'delete_inactivate'
    
    function toggleSelectAllNotice(visible) {
        const notice = document.getElementById('selectAllNotice');
        if (!notice) {
            return;
        }

        if (visible) {
            notice.classList.remove('d-none');
        } else {
            notice.classList.add('d-none');
        }
    }

    function updateToolbarState() {
        const contactCheckboxes = document.querySelectorAll('input.contact-checkbox:checked');
        const count = contactCheckboxes.length;
        const selectAllFlag = document.getElementById('selectAllFlag').value === '1';
        
        // Atualizar texto de contagem
        const selectedCountText = document.getElementById('selectedCountText');
        if (selectAllFlag) {
            const totalContacts = parseInt(document.getElementById('selectAllNotice')?.textContent.match(/\d+/)?.[0] || '0');
            selectedCountText.textContent = `Todos os ${totalContacts} contatos selecionados`;
        } else if (count > 0) {
            selectedCountText.textContent = `${count} contato(s) selecionado(s)`;
        } else {
            selectedCountText.textContent = 'Nenhum contato selecionado';
        }
        
        // Habilitar/desabilitar botões
        const hasSelection = count > 0 || selectAllFlag;
        document.getElementById('btnExportCSV').disabled = !hasSelection;
        document.getElementById('btnDeleteInactivate').disabled = !hasSelection;
        
        // Botão Executar só fica habilitado se tiver ação selecionada
        const btnExecute = document.getElementById('btnExecuteAction');
        btnExecute.disabled = !hasSelection || !currentAction;
    }

    function setAction(action) {
        currentAction = action;
        
        // Resetar visual de todos os botões
        document.getElementById('btnExportCSV').classList.remove('active', 'btn-success');
        document.getElementById('btnExportCSV').classList.add('btn-outline-success');
        document.getElementById('btnDeleteInactivate').classList.remove('active', 'btn-danger');
        document.getElementById('btnDeleteInactivate').classList.add('btn-outline-danger');
        
        // Destacar botão ativo
        if (action === 'export_csv') {
            document.getElementById('btnExportCSV').classList.add('active', 'btn-success');
            document.getElementById('btnExportCSV').classList.remove('btn-outline-success');
        } else if (action === 'delete_inactivate') {
            document.getElementById('btnDeleteInactivate').classList.add('active', 'btn-danger');
            document.getElementById('btnDeleteInactivate').classList.remove('btn-outline-danger');
        }
        
        updateToolbarState();
    }

    function attachSelectAllHandlers() {
        const masterCheckbox = document.getElementById('selectAll');
        const confirmSelectAll = document.getElementById('confirmSelectAll');
        const flagInput = document.getElementById('selectAllFlag');
        const contactCheckboxes = document.querySelectorAll('input.contact-checkbox');

        if (!masterCheckbox || !flagInput) {
            return;
        }

        masterCheckbox.addEventListener('change', function() {
            const checked = masterCheckbox.checked;
            contactCheckboxes.forEach(function(box) { box.checked = checked; });
            flagInput.value = '0';
            toggleSelectAllNotice(checked);
            updateToolbarState();
        });

        if (confirmSelectAll) {
            confirmSelectAll.addEventListener('click', function(event) {
                event.preventDefault();
                flagInput.value = '1';
                toggleSelectAllNotice(false);
                contactCheckboxes.forEach(function(box) { box.checked = true; });
                updateToolbarState();
            });
        }

        contactCheckboxes.forEach(function(box) {
            box.addEventListener('change', function() {
                if (!box.checked) {
                    masterCheckbox.checked = false;
                    flagInput.value = '0';
                    toggleSelectAllNotice(false);
                }
                updateToolbarState();
            });
        });
    }

    function attachToolbarHandlers() {
        const form = document.getElementById('bulkActionsForm');
        const btnExportCSV = document.getElementById('btnExportCSV');
        const btnDeleteInactivate = document.getElementById('btnDeleteInactivate');
        const bulkListsSelect = document.getElementById('bulkListsSelect');
        
        if (!form) return;
        
        // Botão Exportar CSV
        btnExportCSV.addEventListener('click', function() {
            setAction('export_csv');
        });
        
        // Botão Excluir ou Inativar
        btnDeleteInactivate.addEventListener('click', function() {
            setAction('delete_inactivate');
        });
        
        // Select de listas (Select2) - ao selecionar, define ação
        if (bulkListsSelect) {
            $(bulkListsSelect).on('change', function() {
                if ($(this).val()?.length > 0) {
                    setAction('add_lists');
                } else if (currentAction === 'add_lists') {
                    currentAction = null;
                    updateToolbarState();
                }
            });
        }
        
        // Submit do formulário
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const contactCheckboxes = document.querySelectorAll('input.contact-checkbox:checked');
            const selectAllFlag = document.getElementById('selectAllFlag').value === '1';
            
            if (contactCheckboxes.length === 0 && !selectAllFlag) {
                alert('Selecione pelo menos um contato');
                return;
            }
            
            if (!currentAction) {
                alert('Selecione uma ação');
                return;
            }
            
            // Definir action do formulário baseado na ação
            if (currentAction === 'add_lists') {
                const selectedLists = $(bulkListsSelect).val();
                if (!selectedLists || selectedLists.length === 0) {
                    alert('Selecione pelo menos uma lista');
                    return;
                }
                form.action = '/contacts/bulk-assign';
            } else if (currentAction === 'export_csv') {
                form.action = '/contacts/export-csv';
            } else if (currentAction === 'delete_inactivate') {
                if (!confirm('Tem certeza? Contatos sem envios serão EXCLUÍDOS, os demais serão INATIVADOS.')) {
                    return;
                }
                form.action = '/contacts/bulk-delete-inactivate';
            }
            
            // Desabilitar botão para evitar clique duplo
            const btnExecute = document.getElementById('btnExecuteAction');
            btnExecute.disabled = true;
            btnExecute.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            
            form.submit();
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        attachSelectAllHandlers();
        attachToolbarHandlers();
        updateToolbarState();
    });
})();
