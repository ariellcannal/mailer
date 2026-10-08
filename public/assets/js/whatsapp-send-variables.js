(function () {
	'use strict';

	const form = document.querySelector('form[action$="/whatsapp/store"]');
	const templateSelect = document.getElementById('waTemplate');
	const editor = document.getElementById('waVariableEditor');
	const rowsContainer = document.getElementById('waVariableRows');
	const mappingInput = document.getElementById('waVariableMapping');
	if (!form || !templateSelect || !editor || !rowsContainer || !mappingInput) return;

	const contactFields = [
		['name', 'Nome'],
		['nickname', 'Apelido'],
		['email', 'E-mail'],
		['phone', 'Telefone'],
	];
	const mappingsByTemplate = new Map();
	let activeTemplateId = '';

	try {
		const initialMapping = JSON.parse(mappingInput.value || '{}');
		if (templateSelect.value && initialMapping && typeof initialMapping === 'object') {
			mappingsByTemplate.set(templateSelect.value, initialMapping);
		}
	} catch (error) {
		mappingInput.value = '{}';
	}

	function getVariables(text) {
		const variables = [...String(text || '').matchAll(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g)]
			.map((match) => match[1]);
		const uniqueVariables = [...new Set(variables)];
		if (uniqueVariables.length && uniqueVariables.every((variable) => /^\d+$/.test(variable))) {
			uniqueVariables.sort((left, right) => Number(left) - Number(right));
		}
		return uniqueVariables;
	}

	function createSelect(options, value, className) {
		const select = document.createElement('select');
		select.className = `form-select ${className}`;
		options.forEach(([optionValue, label]) => {
			const option = document.createElement('option');
			option.value = optionValue;
			option.textContent = label;
			select.append(option);
		});
		select.value = value;
		return select;
	}

	function createVariableRow(component, variable, saved = {}) {
		const row = document.createElement('div');
		row.className = 'wa-variable-row';
		row.dataset.component = component;
		row.dataset.variable = variable;

		const heading = document.createElement('h6');
		heading.className = 'wa-variable-title';
		heading.textContent = `${component === 'header' ? 'Cabeçalho' : 'Corpo'} · {{${variable}}}`;

		const source = createSelect([
			['contact', 'Informação do contato'],
			['text', 'Texto fixo'],
		], saved.source || 'contact', 'wa-variable-source');

		const contactField = createSelect(contactFields, saved.field || 'name', 'wa-variable-contact');
		const fixedText = document.createElement('input');
		fixedText.type = 'text';
		fixedText.className = 'form-control wa-variable-text';
		fixedText.maxLength = 1024;
		fixedText.placeholder = 'Digite o texto que será enviado';
		fixedText.value = saved.source === 'text' ? (saved.value || '') : '';

		const sourceLabel = document.createElement('label');
		sourceLabel.className = 'wa-variable-field';
		const sourceCaption = document.createElement('span');
		sourceCaption.textContent = 'Preencher com';
		sourceLabel.append(sourceCaption, source);

		const contactLabel = document.createElement('label');
		contactLabel.className = 'wa-variable-field wa-variable-contact-field';
		const contactCaption = document.createElement('span');
		contactCaption.textContent = 'Campo do contato';
		contactLabel.append(contactCaption, contactField);

		const textLabel = document.createElement('label');
		textLabel.className = 'wa-variable-field wa-variable-text-field';
		const textCaption = document.createElement('span');
		textCaption.textContent = 'Texto enviado';
		textLabel.append(textCaption, fixedText);

		function updateMode() {
			const useText = source.value === 'text';
			contactLabel.classList.toggle('d-none', useText);
			textLabel.classList.toggle('d-none', !useText);
			storeMapping();
		}

		source.addEventListener('change', updateMode);
		contactField.addEventListener('change', storeMapping);
		fixedText.addEventListener('input', storeMapping);
		row.append(heading, sourceLabel, contactLabel, textLabel);
		rowsContainer.append(row);
		updateMode();
	}

	function readMapping() {
		const mapping = {};
		rowsContainer.querySelectorAll('.wa-variable-row').forEach((row) => {
			const component = row.dataset.component;
			const variable = row.dataset.variable;
			const source = row.querySelector('.wa-variable-source').value;
			if (!mapping[component]) mapping[component] = {};
			mapping[component][variable] = source === 'text'
				? { source, value: row.querySelector('.wa-variable-text').value }
				: { source, field: row.querySelector('.wa-variable-contact').value };
		});
		return mapping;
	}

	function storeMapping() {
		if (!activeTemplateId) return;
		const mapping = readMapping();
		mappingsByTemplate.set(activeTemplateId, mapping);
		mappingInput.value = JSON.stringify(mapping);
	}

	function renderVariables() {
		if (activeTemplateId) storeMapping();
		activeTemplateId = templateSelect.value;
		rowsContainer.replaceChildren();
		if (!activeTemplateId) {
			editor.classList.add('d-none');
			mappingInput.value = '{}';
			return;
		}

		const option = templateSelect.options[templateSelect.selectedIndex];
		let template;
		try {
			template = JSON.parse(option.dataset.template || '{}');
		} catch (error) {
			editor.classList.add('d-none');
			mappingInput.value = '{}';
			return;
		}

		const variables = {};
		if (String(template.header_type).toUpperCase() === 'TEXT') {
			variables.header = getVariables(template.header_content);
		}
		variables.body = getVariables(template.body_content);
		const saved = mappingsByTemplate.get(activeTemplateId) || {};
		Object.entries(variables).forEach(([component, names]) => {
			names.forEach((name) => createVariableRow(component, name, saved[component]?.[name] || {}));
		});

		if (rowsContainer.children.length === 0) {
			const note = document.createElement('p');
			note.className = 'text-secondary small mb-0';
			note.textContent = 'Este template não possui variáveis para configurar.';
			rowsContainer.append(note);
		}
		editor.classList.remove('d-none');
		storeMapping();
	}

	if (window.jQuery) {
		window.jQuery(templateSelect).on('change.waVariableMapping', renderVariables);
	} else {
		templateSelect.addEventListener('change', renderVariables);
	}
	form.addEventListener('submit', storeMapping);
	renderVariables();
})();