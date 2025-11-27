(function () {
    'use strict';

    const form = document.getElementById('messageForm');
    if (!form) {
        return;
    }

    const storeUrl = form.dataset.storeUrl;
    const indexUrl = form.dataset.indexUrl;
    const contactsUrl = form.dataset.contactsUrl;
    const progressUrl = form.dataset.progressUrl;
    const messageIdInput = document.getElementById('messageId');
    const maxStep = 6;
    let currentStep = 1;

    const contactSelect = document.getElementById('contactListSelect');
    const selectedLists = document.getElementById('selectedLists');
    const recipientTotal = document.getElementById('recipientTotal');
    const scheduledAt = document.getElementById('scheduledAt');

    function showFeedback(message, type = 'error') {
        if (window.alertify && typeof window.alertify[type] === 'function') {
            window.alertify[type](message);
            return;
        }

        window.alert(message);
    }

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

    /**
     * Exibe a etapa solicitada e dispara a pré-visualização quando necessário.
     *
     * @param {number} step Número da etapa a ser exibida.
     * @returns {void}
     */
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

        if (step === 3) {
            waitForEditorReady().then(() => {
                const previewRenderer = window.renderEditorPreview;
                if (typeof previewRenderer === 'function') {
                    previewRenderer('previewPane');
                }
            });
        }
    }

    /**
     * Aguarda a inicialização do editor rico.
     *
     * @returns {Promise<unknown>}
     */
    async function waitForEditorReady(timeoutMs = 5000) {
        const readyPromise = (window.richEditorReady && typeof window.richEditorReady.then === 'function')
            ? window.richEditorReady
            : Promise.resolve(null);

        const timeout = new Promise((resolve) => {
            setTimeout(() => resolve(null), timeoutMs);
        });

        try {
            return await Promise.race([readyPromise, timeout]);
        } catch (error) {
            console.error('Falha ao aguardar o editor rico:', error);
            return null;
        }
    }

    async function validateCurrentStep() {
        const currentContainer = document.querySelector(`.step-content[data-step="${currentStep}"]`);

        if (!currentContainer) {
            return true;
        }

        const fields = currentContainer.querySelectorAll('input, select, textarea');

        for (let index = 0; index < fields.length; index++) {
            const field = fields[index];

            if (typeof field.reportValidity === 'function' && !field.reportValidity()) {
                return false;
            }
        }

        if (currentStep === 2 || currentStep === 3) {
            await waitForEditorReady();
            const html = typeof getRichEditorData === 'function' ? getRichEditorData() : '';

            if (!html || html.trim() === '') {
                showFeedback('Preencha o conteúdo do email antes de continuar.');
                return false;
            }
        }

        if (currentStep === 4) {
            const selected = window.jQuery ? window.jQuery('#contactListSelect').val() || [] : [];
            const hasOptions = contactSelect ? contactSelect.options.length > 0 : false;

            if (hasOptions && selected.length === 0) {
                showFeedback('Selecione pelo menos uma lista de contato.');
                return false;
            }
        }

        return true;
    }

    async function persistStep() {
        if (!progressUrl) {
            return true;
        }

        await waitForEditorReady();

        if (typeof window.syncEditors === 'function') {
            window.syncEditors();
        }
        const formData = new FormData(form);
        formData.set('step', currentStep);

        try {
            const response = await fetch(progressUrl, { method: 'POST', body: formData });
            if (!response.ok) {
                showFeedback('Não foi possível salvar o progresso.');
                return false;
            }
            const payload = await response.json();

            if (!payload.success) {
                showFeedback(payload.error || 'Não foi possível salvar o progresso.');
                return false;
            }

            if (payload.message_id && messageIdInput) {
                messageIdInput.value = payload.message_id;
            }

            return true;
        } catch (error) {
            showFeedback('Falha ao salvar o progresso.');
            return false;
        }
    }

    async function nextStep() {
        if (!(await validateCurrentStep())) {
            return;
        }

        if (!(await persistStep())) {
            return;
        }

        if (currentStep === 2) {
            await waitForEditorReady();
            if (typeof window.renderEditorPreview === 'function') {
                window.renderEditorPreview('previewPane');
            }
        }

        currentStep = Math.min(maxStep, currentStep + 1);

        if (currentStep === 5) {
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
                    showFeedback(payload.error || 'Não foi possível carregar os contatos.');
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
                showFeedback('Erro ao buscar contatos das listas.');
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

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!(await validateCurrentStep())) {
            return;
        }
        await persistStep();
        await waitForEditorReady();
        if (typeof window.syncEditors === 'function') {
            window.syncEditors();
        }
        const body = new URLSearchParams(new FormData(form));
        fetch(storeUrl, { method: 'POST', body })
            .then((response) => response.json())
            .then((payload) => {
            if (payload.success) {
                showFeedback('Mensagem salva com sucesso!', 'success');
                setTimeout(() => { window.location.href = indexUrl; }, 1500);
            } else {
                showFeedback(payload.error || 'Erro ao salvar mensagem');
            }
        })
        .catch(() => { showFeedback('Erro ao salvar mensagem'); });
    });

    window.nextStep = nextStep;
    window.prevStep = prevStep;
    window.renderEditorPreview = renderEditorPreview;
    window.toggleEditorFullscreen = toggleEditorFullscreen;
})();
