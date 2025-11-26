(function () {
    'use strict';

    const form = document.getElementById('messageForm');
    if (!form) {
        return;
    }

    const storeUrl = form.dataset.storeUrl;
    const indexUrl = form.dataset.indexUrl;
    const contactsUrl = form.dataset.contactsUrl;
    let currentStep = 1;

    const contactSelect = document.getElementById('contactListSelect');
    const selectedLists = document.getElementById('selectedLists');
    const recipientTotal = document.getElementById('recipientTotal');
    const scheduledAt = document.getElementById('scheduledAt');

    function updateScheduleSummary() {
        const selectedText = (selectedLists?.textContent || '').trim() || 'Nenhuma lista selecionada.';
        const total = recipientTotal?.textContent || '0';
        const scheduledValue = scheduledAt?.value || '';

        const formatted = (() => {
            if (!scheduledValue) {
                return 'não definido';
            }
            const date = new Date(scheduledValue);
            if (Number.isNaN(date.getTime())) {
                return 'não definido';
            }
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = String(date.getFullYear()).slice(-2);
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        })();

        const summary = document.getElementById('scheduleSummary');
        if (summary) {
            summary.innerHTML = `<strong>Resumo:</strong> ${selectedText}<br>Total estimado: <strong>${total}</strong><br>Envio inicial: <strong>${formatted}</strong>`;
        }
    }

    function showStep(step) {
        document.querySelectorAll('.step-content').forEach((element) => {
            const elementStep = Number(element.getAttribute('data-step'));
            element.style.display = elementStep === step ? '' : 'none';
        });
        document.querySelectorAll('.step').forEach((element) => {
            const elementStep = Number(element.getAttribute('data-step'));
            element.classList.toggle('active', elementStep === step);
            element.classList.toggle('completed', elementStep < step);
        });
    }

    function nextStep() {
        if (currentStep === 2) {
            syncEditors();
        }
        const selected = window.jQuery ? window.jQuery('#contactListSelect').val() || [] : [];
        const hasOptions = contactSelect ? contactSelect.options.length > 0 : false;
        if (currentStep === 3 && hasOptions && selected.length === 0) {
            if (window.alertify) {
                window.alertify.error('Selecione pelo menos uma lista de contato.');
            }
            return;
        }
        currentStep = Math.min(4, currentStep + 1);
        if (currentStep === 4) {
            updateScheduleSummary();
        }
        showStep(currentStep);
    }

    function prevStep() {
        currentStep = Math.max(1, currentStep - 1);
        showStep(currentStep);
    }

    function atualizarDestinatariosPorLista() {
        if (!contactSelect) {
            return;
        }
        const selected = window.jQuery ? window.jQuery('#contactListSelect').val() || [] : [];
        if (selected.length === 0) {
            if (selectedLists) {
                selectedLists.textContent = 'Nenhuma lista selecionada.';
            }
            if (recipientTotal) {
                recipientTotal.textContent = '0';
            }
            return;
        }

        const body = new URLSearchParams();
        selected.forEach((value) => body.append('listas[]', value));

        fetch(contactsUrl, { method: 'POST', body })
            .then((response) => response.json())
            .then((payload) => {
                if (!payload.success) {
                    if (window.alertify) {
                        window.alertify.error(payload.error || 'Não foi possível carregar os contatos.');
                    }
                    return;
                }
                const summary = (payload.lists || [])
                    .map((list) => `<span class="badge bg-secondary me-1 mb-1">${list.name} (${list.contacts})</span>`)
                    .join(' ');
                if (selectedLists) {
                    selectedLists.innerHTML = summary || 'Nenhuma lista selecionada.';
                }
                if (recipientTotal) {
                    recipientTotal.textContent = payload.total_contacts || 0;
                }
            })
            .catch(() => {
                if (window.alertify) {
                    window.alertify.error('Erro ao buscar contatos das listas.');
                }
            });
    }

    if (window.jQuery && window.jQuery.fn.select2 && contactSelect) {
        window.jQuery(contactSelect).select2({
            width: '100%',
            placeholder: window.jQuery(contactSelect).data('placeholder') || 'Selecione as listas'
        });
        window.jQuery(contactSelect).on('change', () => {
            atualizarDestinatariosPorLista();
            updateScheduleSummary();
        });
    }

    scheduledAt?.addEventListener('change', updateScheduleSummary);
    atualizarDestinatariosPorLista();
    updateScheduleSummary();
    showStep(currentStep);

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        syncEditors();
        const body = new URLSearchParams(new FormData(form));
        fetch(storeUrl, { method: 'POST', body })
            .then((response) => response.json())
            .then((payload) => {
                if (payload.success) {
                    if (window.alertify) {
                        window.alertify.success('Mensagem salva com sucesso!');
                    }
                    setTimeout(() => { window.location.href = indexUrl; }, 1500);
                } else if (window.alertify) {
                    window.alertify.error(payload.error || 'Erro ao salvar mensagem');
                }
            })
            .catch(() => { window.alertify?.error('Erro ao salvar mensagem'); });
    });

    window.nextStep = nextStep;
    window.prevStep = prevStep;
    window.switchEditorMode = switchEditorMode;
    window.renderEditorPreview = renderEditorPreview;
    window.toggleEditorFullscreen = toggleEditorFullscreen;
})();
