(function () {
	'use strict';

	const form = document.getElementById('whatsappTemplateForm');
	if (!form) return;

	const nameInput = form.querySelector('[data-wa-preview-name]');
	const categoryInput = form.querySelector('[data-wa-preview-category]');
	const headerTypeInput = form.querySelector('[data-wa-header-type]');
	const headerInput = form.querySelector('[data-wa-preview-header]');
	const mediaFileInput = form.querySelector('[data-wa-preview-media-file]');
	const bodyInput = form.querySelector('[data-wa-preview-body]');
	const footerInput = form.querySelector('[data-wa-preview-footer]');
	const headerField = form.querySelector('[data-wa-header-field]');
	const mediaField = form.querySelector('[data-wa-media-field]');
	const headerHint = form.querySelector('[data-wa-header-hint]');
	const headerTextPreview = document.querySelector('[data-wa-preview-header-text]');
	const mediaPreview = document.querySelector('[data-wa-preview-media]');
	const mediaPlaceholder = mediaPreview.querySelector('[data-wa-preview-media-placeholder]');
	const mediaOpenButton = mediaPreview.querySelector('[data-wa-preview-open-media]');
	const previewImage = mediaOpenButton.querySelector('[data-wa-preview-image]');
	const mediaViewer = document.querySelector('[data-wa-media-viewer]');
	const mediaViewerImage = mediaViewer.querySelector('[data-wa-media-viewer-image]');
	const mediaViewerClose = mediaViewer.querySelector('[data-wa-media-viewer-close]');
	const bodyPreview = document.querySelector('[data-wa-preview-body-text]');
	const footerPreview = document.querySelector('[data-wa-preview-footer-text]');
	const categoryPreview = document.querySelector('[data-wa-preview-category-label]');
	const bodyCount = document.querySelector('[data-wa-body-count]');
	const buttonFields = document.getElementById('waButtonFields');
	const buttonsJson = document.getElementById('waButtonsJson');
	const addButton = document.getElementById('addWaButton');
	const buttonPreview = document.querySelector('[data-wa-preview-buttons]');
	const thumbnailConfig = Object.freeze({
		maxWidth: 320,
		maxHeight: 400,
		minAspectRatio: 1 / 2,
		maxAspectRatio: 2,
		objectPosition: '50% 50%'
	});
	mediaPreview.style.setProperty('--wa-thumbnail-max-width', `${thumbnailConfig.maxWidth}px`);
	let previewFile;
	let previewMediaUrl = '';
	let resizeObserver = null;
	let resizeFallback = false;

	function showMediaPlaceholder() {
		mediaOpenButton.classList.add('d-none');
		mediaPlaceholder.classList.remove('d-none');
		mediaPreview.style.removeProperty('width');
		mediaPreview.style.removeProperty('height');
	}

	function updateThumbnailDimensions() {
		if (!previewImage.complete || !previewImage.naturalWidth || !previewImage.naturalHeight) return;

		const message = mediaPreview.closest('.wa-preview-message');
		const messageStyle = getComputedStyle(message);
		const horizontalPadding = parseFloat(messageStyle.paddingLeft) + parseFloat(messageStyle.paddingRight);
		const availableWidth = Math.max(1, Math.min(thumbnailConfig.maxWidth, message.clientWidth - horizontalPadding));
		const aspectRatio = previewImage.naturalWidth / previewImage.naturalHeight;
		const minimumHeight = availableWidth / thumbnailConfig.maxAspectRatio;
		const maximumHeight = Math.min(thumbnailConfig.maxHeight, availableWidth / thumbnailConfig.minAspectRatio);
		const naturalHeight = availableWidth / aspectRatio;
		const width = availableWidth;
		const height = Math.min(maximumHeight, Math.max(minimumHeight, naturalHeight));

		mediaPreview.style.width = `${width}px`;
		mediaPreview.style.height = `${height}px`;
		previewImage.style.objectPosition = mediaPreview.dataset.objectPosition || thumbnailConfig.objectPosition;
		mediaPlaceholder.classList.add('d-none');
		mediaOpenButton.classList.remove('d-none');
	}

	function setPreviewFile(file) {
		if (file === previewFile) return;
		previewFile = file;
		if (previewMediaUrl) URL.revokeObjectURL(previewMediaUrl);
		previewMediaUrl = '';
		mediaViewer.close();
		mediaViewerImage.removeAttribute('src');
		showMediaPlaceholder();

		if (!file) {
			previewImage.removeAttribute('src');
			return;
		}

		previewMediaUrl = URL.createObjectURL(file);
		previewImage.src = previewMediaUrl;
		if (previewImage.complete && previewImage.naturalWidth) updateThumbnailDimensions();
	}

	function cleanupPreview(event) {
		if (event.persisted) return;
		if (resizeObserver) resizeObserver.disconnect();
		if (resizeFallback) window.removeEventListener('resize', updateThumbnailDimensions);
		if (previewMediaUrl) URL.revokeObjectURL(previewMediaUrl);
		window.removeEventListener('pagehide', cleanupPreview);
	}

	function fieldLabel(text, control, className) {
		const field = document.createElement('label');
		field.className = `wa-button-field ${className}`;
		const caption = document.createElement('span');
		caption.textContent = text;
		field.append(caption, control);
		return field;
	}

	function createButtonField(button = {}) {
		if (buttonFields.children.length >= 3) return;

		const row = document.createElement('div');
		row.className = 'wa-button-row';
		const type = document.createElement('select');
		type.className = 'form-select';
		type.dataset.buttonType = '';
		[
			['QUICK_REPLY', 'Resposta rápida'],
			['URL', 'Abrir URL'],
			['PHONE_NUMBER', 'Ligar'],
		].forEach(([value, label]) => {
			const option = document.createElement('option');
			option.value = value;
			option.textContent = label;
			type.append(option);
		});
		type.value = button.type || 'QUICK_REPLY';

		const label = document.createElement('input');
		label.className = 'form-control';
		label.maxLength = 25;
		label.placeholder = 'Texto do botão';
		label.value = button.text || '';
		label.dataset.buttonText = '';

		const target = document.createElement('input');
		target.className = 'form-control wa-button-target';
		target.placeholder = type.value === 'URL' ? 'https://...' : 'Telefone';
		target.value = button.url || button.phone_number || '';
		target.dataset.buttonTarget = '';

		const remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'btn btn-outline-danger';
		remove.title = 'Remover botão';
		remove.setAttribute('aria-label', 'Remover botão');
		remove.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
		remove.addEventListener('click', function() {
			row.remove();
			updatePreview();
		});

		const targetField = fieldLabel('Destino', target, 'wa-button-destination');
		function updateTarget() {
			target.disabled = type.value === 'QUICK_REPLY';
			targetField.classList.toggle('d-none', target.disabled);
			target.type = type.value === 'URL' ? 'url' : 'tel';
			target.placeholder = type.value === 'URL' ? 'https://...' : 'Telefone';
		}
		type.addEventListener('change', updateTarget);
		row.append(fieldLabel('Tipo', type, 'wa-button-type'), fieldLabel('Texto do botão', label, 'wa-button-label'), remove, targetField);
		buttonFields.append(row);
		updateTarget();
	}

	function readButtons() {
		const buttons = [];
		buttonFields.querySelectorAll('.wa-button-row').forEach((row) => {
			const type = row.querySelector('[data-button-type]').value;
			const text = row.querySelector('[data-button-text]').value.trim();
			const target = row.querySelector('[data-button-target]').value.trim();
			if (!text) return;
			if (type === 'URL') buttons.push({ type, text, url: target });
			else if (type === 'PHONE_NUMBER') buttons.push({ type, text, phone_number: target });
			else buttons.push({ type, text });
		});
		return buttons;
	}

	function updatePreview() {
		const headerType = headerTypeInput.value;
		const headerValue = headerInput.value.trim();
		headerField.classList.toggle('d-none', headerType === 'NONE' || headerType === 'IMAGE');
		mediaField.classList.toggle('d-none', headerType !== 'IMAGE');
		headerHint.textContent = 'Texto curto exibido acima da mensagem.';

		headerTextPreview.classList.toggle('d-none', headerType !== 'TEXT' || headerValue === '');
		headerTextPreview.textContent = headerValue;
		setPreviewFile(mediaFileInput.files[0] || null);
		previewImage.alt = `Mídia do template ${nameInput.value}`;
		mediaPreview.classList.toggle('d-none', headerType !== 'IMAGE');

		bodyPreview.textContent = bodyInput.value || 'Corpo da mensagem...';
		bodyPreview.classList.toggle('is-placeholder', bodyInput.value.trim() === '');
		footerPreview.textContent = footerInput.value;
		footerPreview.classList.toggle('d-none', footerInput.value.trim() === '');
		categoryPreview.textContent = categoryInput.options[categoryInput.selectedIndex].text;
		bodyCount.textContent = bodyInput.value.length;

		const buttons = readButtons();
		buttonsJson.value = JSON.stringify(buttons);
		buttonPreview.replaceChildren();
		buttons.forEach((button) => {
			const action = document.createElement('div');
			action.className = 'wa-preview-action';
			const icon = document.createElement('i');
			icon.className = button.type === 'URL' ? 'fas fa-external-link-alt' : (button.type === 'PHONE_NUMBER' ? 'fas fa-phone' : 'fas fa-reply');
			icon.setAttribute('aria-hidden', 'true');
			const text = document.createElement('span');
			text.textContent = button.text;
			action.append(icon, text);
			buttonPreview.append(action);
		});
		buttonPreview.classList.toggle('d-none', buttons.length === 0);
		addButton.disabled = buttonFields.children.length >= 3;
	}

	let initialButtons = [];
	try {
		initialButtons = JSON.parse(buttonsJson.value || '[]');
	} catch (error) {
		initialButtons = [];
	}
	if (Array.isArray(initialButtons)) initialButtons.slice(0, 3).forEach(createButtonField);
	addButton.addEventListener('click', function() {
		createButtonField();
		updatePreview();
	});
	form.addEventListener('input', updatePreview);
	form.addEventListener('change', updatePreview);
	if (window.jQuery) {
		window.jQuery(form).on('change.waPreview', 'select', updatePreview);
	}
	form.addEventListener('submit', updatePreview);
	previewImage.addEventListener('load', updateThumbnailDimensions);
	previewImage.addEventListener('error', showMediaPlaceholder);
	mediaOpenButton.addEventListener('click', function () {
		if (!previewMediaUrl || !previewImage.naturalWidth) return;
		mediaViewerImage.src = previewMediaUrl;
		mediaViewer.showModal();
	});
	mediaViewerClose.addEventListener('click', () => mediaViewer.close());
	mediaViewer.addEventListener('click', (event) => {
		if (event.target === mediaViewer) mediaViewer.close();
	});
	if ('ResizeObserver' in window) {
		resizeObserver = new ResizeObserver(updateThumbnailDimensions);
		resizeObserver.observe(mediaPreview.closest('.wa-preview-message'));
	} else {
		resizeFallback = true;
		window.addEventListener('resize', updateThumbnailDimensions, { passive: true });
	}
	window.addEventListener('pagehide', cleanupPreview);
	updatePreview();
})();