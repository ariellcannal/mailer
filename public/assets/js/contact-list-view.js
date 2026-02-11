(function() {
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

    function attachSelectAllHandlers() {
        const masterCheckbox = document.getElementById('selectAll');
        const confirmSelectAll = document.getElementById('confirmSelectAll');
        const flagInput = document.getElementById('selectAllFlag');
        const contactCheckboxes = document.querySelectorAll('input.contact-checkbox');
        const bulkRemoveBtn = document.getElementById('bulkRemoveBtn');

        if (!masterCheckbox || !flagInput || !bulkRemoveBtn) {
            return;
        }

        // Atualizar estado do botão baseado em seleções
        function updateButtonState() {
            const anyChecked = Array.from(contactCheckboxes).some(box => box.checked);
            bulkRemoveBtn.disabled = !anyChecked;
        }

        // Inicializar estado do botão
        updateButtonState();

        // Master checkbox seleciona/deseleciona todos
        masterCheckbox.addEventListener('change', function() {
            const checked = masterCheckbox.checked;
            contactCheckboxes.forEach(function(box) { 
                box.checked = checked; 
            });
            flagInput.value = '0';
            toggleSelectAllNotice(checked);
            updateButtonState();
        });

        // Confirmar seleção de todos (incluindo páginas não visíveis)
        if (confirmSelectAll) {
            confirmSelectAll.addEventListener('click', function(event) {
                event.preventDefault();
                flagInput.value = '1';
                toggleSelectAllNotice(false);
                contactCheckboxes.forEach(function(box) { 
                    box.checked = true; 
                });
                updateButtonState();
            });
        }

        // Atualizar master checkbox quando checkboxes individuais mudam
        contactCheckboxes.forEach(function(box) {
            box.addEventListener('change', function() {
                if (!box.checked) {
                    masterCheckbox.checked = false;
                    flagInput.value = '0';
                    toggleSelectAllNotice(false);
                }
                updateButtonState();
            });
        });

        // Prevenção de clique duplo no botão de remoção
        const bulkRemoveForm = document.getElementById('bulkRemoveForm');
        if (bulkRemoveForm) {
            bulkRemoveForm.addEventListener('submit', function() {
                bulkRemoveBtn.disabled = true;
                bulkRemoveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removendo...';
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        attachSelectAllHandlers();
    });
})();
