(function() {
	'use strict';

	const $form = $('#messageForm');
	if ($form.length === 0) {
		return;
	}

	const storeUrl = $form.data('storeUrl');
	const indexUrl = $form.data('indexUrl');
	const contactsUrl = $form.data('contactsUrl');
	const progressUrl = $form.data('progressUrl');

	const $messageIdInput = $('#messageId');
	const $contactSelect = $('#contactListSelect');
	const $selectedLists = $('#selectedLists');
	const $recipientTotal = $('#recipientTotal');
	const $scheduledAt = $('#scheduledAt');

	const maxStep = 6;
	let currentStep = 1;

	/* ===========================================================
	 *  Helpers
	 * =========================================================== */

	function showFeedback(message, type = 'error') {
		if (window.alertify && typeof alertify[type] === 'function') {
			alertify[type](message);
			return;
		}
		alert(message);
	}

	function updateScheduleSummary() {
		const selectedText = ($selectedLists.text() || '').trim() || 'Nenhuma lista selecionada.';
		const total = $recipientTotal.text() || '0';
		const scheduledValue = $scheduledAt.val() || '';

		let formatted = 'não definido';

		if (scheduledValue) {
			const date = new Date(scheduledValue);
			if (!isNaN(date.getTime())) {
				const day = String(date.getDate()).padStart(2, '0');
				const month = String(date.getMonth() + 1).padStart(2, '0');
				const year = String(date.getFullYear()).slice(-2);
				const hours = String(date.getHours()).padStart(2, '0');
				const minutes = String(date.getMinutes()).padStart(2, '0');
				formatted = `${day}/${month}/${year} ${hours}:${minutes}`;
			}
		}

		$('#scheduleSummary').html(
			`<strong>Resumo:</strong> ${selectedText}<br>
             Total estimado: <strong>${total}</strong><br>
             Envio inicial: <strong>${formatted}</strong>`
		);
	}

	/* ===========================================================
	 *  Steps
	 * =========================================================== */

	function showStep(step) {
		$('.step-content').each(function() {
			$(this).toggle($(this).data('step') === step);
		});

		$('.step').each(function() {
			const elementStep = Number($(this).data('step'));
			$(this).toggleClass('active', elementStep === step);
			$(this).toggleClass('completed', elementStep < step);
		});

		if (step === 3 && typeof window.renderEditorPreview === 'function') {
			window.renderEditorPreview('previewPane');
		}
	}

	function validateCurrentStep() {
		const $current = $(`.step-content[data-step="${currentStep}"]`);
		const $fields = $current.find('input, select, textarea');

		if (currentStep !== 2 && currentStep !== 3) {
			let valid = true;
			$fields.each(function() {
				if (this.checkValidity && !this.checkValidity()) {
					this.reportValidity();
					valid = false;
					return false;
				}
			});
			if (!valid) return false;
		}

		if (currentStep === 2 || currentStep === 3) {
			const html = typeof getRichEditorData === 'function' ? getRichEditorData() : '';
			if (!html || html.trim() === '') {
				showFeedback('Preencha o conteúdo do email antes de continuar.');
				return false;
			}
		}

		if (currentStep === 4) {
			const selected = $contactSelect.val() || [];
			if (($contactSelect[0].options.length > 0) && selected.length === 0) {
				showFeedback('Selecione pelo menos uma lista de contato.');
				return false;
			}
		}

		return true;
	}

	function persistStep() {
		if (!progressUrl) return true;

		const formData = new FormData($form[0]);
		formData.set('step', currentStep);
		formData.set('html_content', window.getRichEditorData());

		return $.ajax({
			url: progressUrl,
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false
		}).done(function(payload) {
			if (!payload.success) {
				showFeedback(payload.error || 'Não foi possível salvar o progresso.');
			}
			if (payload.message_id) {
				$messageIdInput.val(payload.message_id);
			}
			return payload;
		}).fail(function() {
			showFeedback('Falha ao salvar o progresso.');
			return payload;
		});
	}

	function nextStep() {
		if (!validateCurrentStep()) return;

		$.when(persistStep()).done(function(payload) {
			if (!payload.success) return;
			
			if (currentStep === 2 && typeof window.renderEditorPreview === 'function') {
				window.renderEditorPreview('previewPane');
			}

			currentStep = Math.min(maxStep, currentStep + 1);

			if (currentStep === 5) {
				updateScheduleSummary();
			}

			showStep(currentStep);
		});
	}

	function prevStep() {
		currentStep = Math.max(1, currentStep - 1);
		showStep(currentStep);
	}

	/* ===========================================================
	 *  Contatos / Listas
	 * =========================================================== */

	function atualizarDestinatariosPorLista() {
		const selected = $contactSelect.val() || [];

		if (selected.length === 0) {
			$selectedLists.text('Nenhuma lista selecionada.');
			$recipientTotal.text('0');
			return;
		}

		$.ajax({
			url: contactsUrl,
			method: 'POST',
			data: { 'listas[]': selected }
		})
			.done(function(payload) {
				if (!payload.success) {
					showFeedback(payload.error || 'Erro ao buscar contatos.');
					return;
				}

				const summary = (payload.lists || [])
					.map(list => `<span class="badge bg-secondary me-1 mb-1">${list.name} (${list.contacts})</span>`)
					.join(' ');

				$selectedLists.html(summary || 'Nenhuma lista selecionada.');
				$recipientTotal.text(payload.total_contacts || 0);
			})
			.fail(function() {
				showFeedback('Erro ao buscar contatos das listas.');
			});
	}

	/* ===========================================================
	 *  Inicialização jQuery
	 * =========================================================== */

	$(document).ready(function() {
		// Select2
		if ($contactSelect.length && $.fn.select2) {
			$contactSelect.select2({
				width: '100%',
				placeholder: $contactSelect.data('placeholder') || 'Selecione as listas'
			});

			$contactSelect.on('change', function() {
				atualizarDestinatariosPorLista();
				updateScheduleSummary();
			});
		}

		$scheduledAt.on('change', updateScheduleSummary);

		atualizarDestinatariosPorLista();
		updateScheduleSummary();
		showStep(currentStep);

		// Navegação
		$('.prevStep').on('click', prevStep);
		$('.nextStep').on('click', nextStep);

		/* Envio final */
		$form.on('submit', function(e) {
			e.preventDefault();
			if (!validateCurrentStep()) return;
			persistStep();
		});
	});

	window.nextStep = nextStep;
	window.prevStep = prevStep;
})();
