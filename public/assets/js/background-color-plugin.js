/**
 * BackgroundColorPlugin para CKEditor 5
 * Permite selecionar cor de fundo do email (aplicada via CSS inline na tag body)
 * Funciona exatamente como FontColor
 */

class BackgroundColorPlugin extends CKEDITOR.Plugin {
	static get pluginName() {
		return 'BackgroundColorPlugin';
	}

	init() {
		const editor = this.editor;
		const { commands } = editor;

		// Inicializa cor de fundo padrão
		if (!window.emailBackgroundColor) {
			window.emailBackgroundColor = '#ffffff';
		}

		// Comando para definir cor de fundo
		commands.add('setBackgroundColor', {
			execute: (color) => {
				window.emailBackgroundColor = color;
				
				// Atualiza preview
				if (window.updateEmailPreview) {
					window.updateEmailPreview();
				}
			}
		});

		// Adiciona botão à toolbar usando ColorUI (igual FontColor)
		editor.ui.componentFactory.add('BackgroundColor', (locale) => {
			const colorPickerView = new CKEDITOR.ColorPickerView(locale);
			
			colorPickerView.set({
				colors: DEFAULT_HEX_COLORS.map(c => c.color),
				columns: 5
			});

			const dropdownView = CKEDITOR.createDropdown(locale);
			dropdownView.buttonView.set({
				label: 'Cor de Fundo',
				tooltip: 'Cor de fundo do email',
				withText: false,
				isToggleable: true
			});

			// Ícone do botão
			dropdownView.buttonView.element.innerHTML = `
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
					<rect x="2" y="2" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5"/>
					<rect x="3" y="3" width="14" height="14" fill="${window.emailBackgroundColor}" stroke="currentColor" stroke-width="0.5"/>
				</svg>
			`;

			dropdownView.panelView.children.add(colorPickerView);

			// Listener para quando cor é selecionada
			this.listenTo(colorPickerView, 'execute', evt => {
				const color = evt.source.value;
				commands.execute('setBackgroundColor', color);
				
				// Atualiza ícone do botão
				dropdownView.buttonView.element.innerHTML = `
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
						<rect x="2" y="2" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5"/>
						<rect x="3" y="3" width="14" height="14" fill="${color}" stroke="currentColor" stroke-width="0.5"/>
					</svg>
				`;
				
				dropdownView.isOpen = false;
			});

			return dropdownView;
		});

		// Detectar cor de fundo ao sair do modo "Fonte" (SourceEditing)
		const sourceEditing = editor.plugins.get('SourceEditing');
		if (sourceEditing) {
			sourceEditing.on('change:isSourceEditingMode', (evt, propertyName, newValue) => {
				if (!newValue) { // Saindo do modo fonte
					setTimeout(() => {
						const html = editor.getData();
						
						// Detectar cor inline na body
						const bodyMatch = html.match(/<body[^>]*style="[^"]*background-color:\s*([^;]+);[^"]*"/i);
						if (bodyMatch && bodyMatch[1]) {
							const color = bodyMatch[1].trim();
							window.emailBackgroundColor = color;
							commands.execute('setBackgroundColor', color);
							return;
						}
						
						// Detectar cor em <style>
						const styleMatch = html.match(/body\s*{\s*[^}]*background-color:\s*([^;]+);/i);
						if (styleMatch && styleMatch[1]) {
							const color = styleMatch[1].trim();
							window.emailBackgroundColor = color;
							commands.execute('setBackgroundColor', color);
						}
					}, 100);
				}
			});
		}

		// Desabilitar botão no modo "Fonte"
		if (sourceEditing) {
			sourceEditing.on('change:isSourceEditingMode', (evt, propertyName, newValue) => {
				const bgColorButton = editor.ui.view.toolbar.children.find(item => 
					item.buttonView && item.buttonView.label === 'Cor de Fundo'
				);
				
				if (bgColorButton) {
					bgColorButton.isEnabled = !newValue;
				}
			});
		}
	}
}

// Exportar para uso global
window.BackgroundColorPlugin = BackgroundColorPlugin;
