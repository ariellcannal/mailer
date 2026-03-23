/**
 * OBSOLETO
 * GoogleFontsPlugin para CKEditor 5
 * Permite importar fontes do Google Fonts via modal AJAX
 * Adiciona ao menu Formato > Fonte
 */

class GoogleFontsPluginImproved extends CKEDITOR.Plugin {
	static get pluginName() {
		return 'GoogleFontsPluginImproved';
	}

	init() {
		const editor = this.editor;

		// Inicializa array de fontes importadas
		if (!window.importedGoogleFonts) {
			window.importedGoogleFonts = [];
		}

		// Adiciona comando para importar Google Font
		editor.commands.add('importGoogleFont', {
			execute: () => this.openGoogleFontsModal(editor)
		});

		// Adiciona botão ao menu Formato > Fonte
		editor.ui.componentFactory.add('importGoogleFont', (locale) => {
			const button = new CKEDITOR.ButtonView(locale);

			button.set({
				label: 'Importar Google Font',
				tooltip: 'Importar fonte do Google Fonts',
				withText: true,
				icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M10.187 17H5.773c-.637 0-1.092-.138-1.364-.415-.273-.277-.409-.718-.409-1.323V4.738c0-.617.14-1.062.419-1.332.279-.27.73-.406 1.354-.406h4.68c.69 0 1.288.041 1.793.124.506.083.96.242 1.36.478.341.197.644.447.906.75a3.262 3.262 0 0 1 .808 2.162c0 1.401-.722 2.426-2.167 3.075C15.05 10.175 16 11.315 16 13.01a3.756 3.756 0 0 1-2.296 3.504 6.1 6.1 0 0 1-1.517.377c-.571.073-1.238.11-2 .11zm-.217-6.217H7v4.087h3.069c1.977 0 2.965-.69 2.965-2.072 0-.707-.256-1.22-.768-1.537-.512-.319-1.277-.478-2.296-.478zM7 5.13v3.619h2.606c.729 0 1.292-.067 1.69-.2a1.6 1.6 0 0 0 .91-.765c.165-.267.247-.566.247-.897 0-.707-.26-1.176-.778-1.409-.519-.232-1.31-.348-2.375-.348H7z"/></svg>'
			});

			button.on('execute', () => {
				editor.execute('importGoogleFont');
			});

			return button;
		});

		// Detectar Google Fonts ao sair do modo "Fonte"
		const sourceEditing = editor.plugins.get('SourceEditing');
		if (sourceEditing) {
			sourceEditing.on('change:isSourceEditingMode', (evt, propertyName, newValue) => {
				if (!newValue) { // Saindo do modo fonte
					setTimeout(() => {
						const html = editor.getData();
						this.detectGoogleFontsInHtml(html);
					}, 100);
				}
			});
		}
	}

	openGoogleFontsModal(editor) {
		const self = this;
		const modalId = `google-fonts-modal-${Date.now()}`;

		// Cria modal HTML
		const modalHtml = `
			<div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-lg modal-dialog-centered">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Importar Google Font</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<div class="mb-3">
								<label class="form-label">Pesquisar Fonte</label>
								<input type="text" class="form-control" id="google-font-search" placeholder="Ex: Roboto, Open Sans, Montserrat">
								<small class="form-text text-muted">Digite o nome da fonte para pesquisar</small>
							</div>
							<div id="google-fonts-list" class="list-group" style="max-height: 400px; overflow-y: auto;">
								<div class="text-center text-muted p-3">Digite para pesquisar fontes...</div>
							</div>
							<div class="mt-3">
								<label class="form-label">Pesos (opcional)</label>
								<input type="text" class="form-control" id="google-font-weights" placeholder="Ex: 400,700" value="400,700">
								<small class="form-text text-muted">Separe múltiplos pesos por vírgula</small>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
							<button type="button" class="btn btn-primary" id="google-font-confirm">Importar</button>
						</div>
					</div>
				</div>
			</div>
		`;

		// Adiciona modal ao DOM
		const $modal = $(modalHtml);
		$('body').append($modal);
		const bsModal = new bootstrap.Modal($modal[0]);

		let selectedFont = null;

		// Listener para pesquisa
		$modal.find('#google-font-search').on('input', function() {
			const query = $(this).val().trim();
			if (query.length < 2) {
				$modal.find('#google-fonts-list').html('<div class="text-center text-muted p-3">Digite pelo menos 2 caracteres...</div>');
				return;
			}

			// Pesquisar fontes via AJAX
			$.ajax({
				url: '/api/google-fonts/search',
				method: 'GET',
				data: { q: query },
				dataType: 'json',
				success: function(response) {
					if (response.fonts && response.fonts.length > 0) {
						let html = '';
						response.fonts.forEach(font => {
							html += `
								<button type="button" class="list-group-item list-group-item-action google-font-item" data-font="${font.family}">
									<strong>${font.family}</strong>
									<br>
									<small class="text-muted">${font.variants ? font.variants.join(', ') : 'Variantes disponíveis'}</small>
								</button>
							`;
						});
						$modal.find('#google-fonts-list').html(html);

						// Listener para seleção de fonte
						$modal.find('.google-font-item').on('click', function() {
							$modal.find('.google-font-item').removeClass('active');
							$(this).addClass('active');
							selectedFont = $(this).data('font');
						});
					} else {
						$modal.find('#google-fonts-list').html('<div class="alert alert-info m-0">Nenhuma fonte encontrada. Tente outro nome.</div>');
					}
				},
				error: function() {
					$modal.find('#google-fonts-list').html('<div class="alert alert-danger m-0">Erro ao pesquisar fontes. Tente novamente.</div>');
				}
			});
		});

		// Listener para confirmar importação
		$modal.find('#google-font-confirm').on('click', function() {
			if (!selectedFont) {
				alert('Por favor, selecione uma fonte.');
				return;
			}

			const weights = $modal.find('#google-font-weights').val().trim() || '400,700';
			self.importFont(editor, selectedFont, weights);
			bsModal.hide();
		});

		// Limpar modal ao fechar
		$modal.on('hidden.bs.modal', function() {
			$modal.remove();
		});

		bsModal.show();
	}

	importFont(editor, fontName, weights) {
		// Constrói URL do Google Fonts
		const fontUrl = `https://fonts.googleapis.com/css2?family=${fontName.replace(/\s+/g, '+')}:wght@${weights}&display=swap`;

		// Adiciona à lista de fontes importadas
		const fontData = { name: fontName, url: fontUrl, weights };

		// Verifica se já foi importada
		const exists = window.importedGoogleFonts.some(f => f.name === fontName);
		if (!exists) {
			window.importedGoogleFonts.push(fontData);

			// Adiciona fonte ao dropdown de fontes do CKEditor
			const fontFamilyConfig = editor.config.get('fontFamily');
			if (fontFamilyConfig && fontFamilyConfig.options) {
				const fontOption = `${fontName}, sans-serif`;
				if (!fontFamilyConfig.options.includes(fontOption)) {
					fontFamilyConfig.options.push(fontOption);
				}
			}

			// Atualiza preview
			if (window.updateEmailPreview) {
				window.updateEmailPreview();
			}

			alert(`Fonte "${fontName}" importada com sucesso! Agora você pode selecioná-la no dropdown "Fonte".`);
		} else {
			alert(`Fonte "${fontName}" já foi importada anteriormente.`);
		}
	}

	detectGoogleFontsInHtml(html) {
		// Detectar links de Google Fonts no <head>
		const googleFontsRegex = /https:\/\/fonts\.googleapis\.com\/css2\?family=([^"&]+)/g;
		let match;

		while ((match = googleFontsRegex.exec(html)) !== null) {
			const fontUrl = match[0];
			const fontParam = match[1];

			// Decodificar nome da fonte
			const fontName = decodeURIComponent(fontParam.split(':')[0].replace(/\+/g, ' '));

			// Extrair pesos
			const weightsMatch = fontUrl.match(/:wght@([^&]+)/);
			const weights = weightsMatch ? weightsMatch[1] : '400,700';

			// Adicionar à lista se não existir
			const exists = window.importedGoogleFonts.some(f => f.name === fontName);
			if (!exists) {
				window.importedGoogleFonts.push({
					name: fontName,
					url: fontUrl,
					weights: weights
				});
			}
		}
	}
}

// Exportar para uso global
window.GoogleFontsPluginImproved = GoogleFontsPluginImproved;
