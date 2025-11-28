(function($) {
	'use strict';

	const editorReadyDeferred = (() => {
		let resolveReady;
		let rejectReady;
		const promise = new Promise((resolve, reject) => {
			resolveReady = resolve;
			rejectReady = reject;
		});

		return { promise, resolveReady, rejectReady };
	})();

	window.richEditorReady = editorReadyDeferred.promise;

	if (typeof CKEDITOR === 'undefined') {
		console.error('CKEDITOR global is not available. Make sure ckeditor5.umd.js is loaded before rich-editor.js.');
		editorReadyDeferred.resolveReady(null);
		return;
	}

	const DEFAULT_HEX_COLORS = [
		{ color: '#000000', label: 'Black' },
		{ color: '#4D4D4D', label: 'Dim grey' },
		{ color: '#999999', label: 'Grey' },
		{ color: '#E6E6E6', label: 'Light grey' },
		{ color: '#FFFFFF', label: 'White', hasBorder: true },
		{ color: '#E65C5C', label: 'Red' },
		{ color: '#E69C5C', label: 'Orange' },
		{ color: '#E6E65C', label: 'Yellow' },
		{ color: '#C2E65C', label: 'Light green' },
		{ color: '#5CE65C', label: 'Green' },
		{ color: '#5CE6A6', label: 'Aquamarine' },
		{ color: '#5CE6E6', label: 'Turquoise' },
		{ color: '#5CA6E6', label: 'Light blue' },
		{ color: '#5C5CE6', label: 'Blue' },
		{ color: '#A65CE6', label: 'Purple' }
	];

	const {
		ClassicEditor,
		Autosave,
		Essentials,
		Paragraph,
		Autoformat,
		TextTransformation,
		Bold,
		Table,
		TableToolbar,
		FontBackgroundColor,
		FontColor,
		FontFamily,
		FontSize,
		GeneralHtmlSupport,
		Heading,
		ImageInline,
		ImageToolbar,
		CloudServices,
		Link,
		ImageUpload,
		ImageInsertViaUrl,
		AutoImage,
		ImageTextAlternative,
		ImageStyle,
		ImageResize,
		Indent,
		IndentBlock,
		Italic,
		AutoLink,
		List,
		ImageUtils,
		ImageEditing,
		PlainTableOutput,
		Strikethrough,
		Style,
		TableCaption,
		Alignment,
		Underline,
		Fullscreen,
		Emoji,
		Mention,
		MediaEmbed,
		Markdown,
		PasteFromMarkdownExperimental,
		Code,
		Subscript,
		Superscript,
		Highlight,
		BlockQuote,
		HorizontalLine,
		CodeBlock,
		ImageBlock,
		LinkImage,
		ImageCaption,
		TodoList,
		ShowBlocks,
		SourceEditing,
		TextPartLanguage,
		Title,
		BalloonToolbar,
		BlockToolbar,

		Plugin,
		createDropdown,
		Collection,
		UIModel,
		addListToDropdown,
		ButtonView
	} = CKEDITOR;

	const editorPlugins = [
		Alignment,
		Autoformat,
		AutoImage,
		AutoLink,
		Autosave,
		BalloonToolbar,
		BlockQuote,
		BlockToolbar,
		Bold,
		CloudServices,
		Code,
		CodeBlock,
		Emoji,
		Essentials,
		FontBackgroundColor,
		FontColor,
		FontFamily,
		FontSize,
		Fullscreen,
		GeneralHtmlSupport,
		Heading,
		Highlight,
		HorizontalLine,
		ImageBlock,
		ImageCaption,
		ImageEditing,
		ImageInline,
		ImageInsertViaUrl,
		ImageStyle,
		ImageTextAlternative,
		ImageToolbar,
		ImageUpload,
		ImageUtils,
		ImageResize,
		Indent,
		IndentBlock,
		Italic,
		Link,
		LinkImage,
		List,
		MediaEmbed,
		Mention,
		Paragraph,
		PasteFromMarkdownExperimental,
		PlainTableOutput,
		ShowBlocks,
		SourceEditing,
		Strikethrough,
		Style,
		Subscript,
		Superscript,
		Table,
		TableCaption,
		TableToolbar,
		TextPartLanguage,
		TextTransformation,
		TodoList,
		Underline
	].filter(Boolean);

	const configElement = document.getElementById('richEditorConfig');
	if (!configElement) {
		editorReadyDeferred.resolveReady(null);
		return;
	}

	const settings = {
		licence: configElement.dataset.licence || 'GPL',
		height: Number(configElement.dataset.height || 600),
		templateSearchUrl: configElement.dataset.templateSearchUrl || '',
		fileListUrl: configElement.dataset.fileListUrl || '',
		fileUploadUrl: configElement.dataset.fileUploadUrl || ''
	};

	let editorInstance = null;

	/**
	 * Recupera o elemento de origem associado ao editor.
	 *
	 * @returns {HTMLTextAreaElement|null}
	 */
	const getEditorSourceElement = () => document.getElementById('richEditor')
		|| document.querySelector('textarea[name="html_content"]');

	/**
	 * Retorna o HTML atual do editor, mesmo antes da instância carregar.
	 *
	 * @returns {string}
	 */
	window.getRichEditorData = function getRichEditorData() {
		if (editorInstance?.getData) {
			return editorInstance.getData();
		}

		const sourceElement = getEditorSourceElement();
		return sourceElement ? sourceElement.value : '';
	};

	/**
	 * Sincroniza o editor com o campo original para submissões e salvamentos parciais.
	 *
	 * @returns {void}
	 */
	window.syncRichEditors = function syncRichEditors() {
		const sourceElement = getEditorSourceElement();
		if (sourceElement) {
			sourceElement.value = window.getRichEditorData();
		}
	};

	/**
	 * Garante a altura configurada para o editor, inclusive ao alternar para o modo de código-fonte.
	 *
	 * @param {object} editor Instância do editor.
	 * @param {number} height Altura desejada em pixels.
	 * @returns {void}
	 */
	const enforceEditorHeight = (editor, height) => {
		editor.editing.view.change((writer) => {
			const rootElement = editor.editing.view.document.getRoot();

			if (rootElement) {
				writer.setStyle('height', `${height}px`, rootElement);
				writer.setStyle('min-height', `${height}px`, rootElement);
			}
		});

		const sourceEditing = editor.plugins.get('SourceEditing');

		if (sourceEditing) {
			const applySourceAreaHeight = () => {
				const sourceArea = editor.ui.view.element?.querySelector('.ck-source-editing-area');

				if (sourceArea) {
					sourceArea.style.height = `${height}px`;
					sourceArea.style.minHeight = `${height}px`;
				}
			};

			applySourceAreaHeight();
			sourceEditing.on('change:isSourceEditingMode', applySourceAreaHeight);
		}
	};

	const icons = {
		library: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M17.5 9.303V8h-13v8.5h4.341c.191.54.457 1.044.785 1.5H2a1.5 1.5 0 0 1-1.5-1.5v-13A1.5 1.5 0 0 1 2 2h4.5a1.5 1.5 0 0 1 1.06.44L9.122 4H16a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 19 8v2.531a6 6 0 0 0-1.5-1.228M16 6.5v-1H8.5l-2-2H2v13h1V8a1.5 1.5 0 0 1 1.5-1.5z"></path><path d="M14.5 19.5a5 5 0 1 1 0-10 5 5 0 0 1 0 10M15 14v-2h-1v2h-2v1h2v2h1v-2h2v-1z"></path></svg>',
		templates: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M3 19a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8.022a6.5 6.5 0 0 0-1.5-.709V2a.5.5 0 0 0-.5-.5H3a.5.5 0 0 0-.5.5v15a.5.5 0 0 0 .5.5h6.313c.173.534.412 1.037.709 1.5z"></path><path d="M9.174 14a6.5 6.5 0 0 0-.155 1H6v-1zm.848-2a6.5 6.5 0 0 0-.524 1H4v-1zm2.012-2c-.448.283-.86.62-1.224 1H6v-1zM12 4v1H4V4zm2 3V6H6v1zm1 2V8H7v1z"></path><path d="M20 15.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0M15.5 13a.5.5 0 0 0-.5.5V15h-1.5a.5.5 0 0 0 0 1H15v1.5a.5.5 0 0 0 1 0V16h1.5a.5.5 0 0 0 0-1H16v-1.5a.5.5 0 0 0-.5-.5" clip-rule="evenodd"></path></svg>',
		tags: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="9.8" r="1.5"></circle><path d="M13.25 2.75V2h.035a6 6 0 0 1 .363.014c.21.013.517.041.785.109.397.1.738.281 1.007.55s.429.587.524.907c.182.608.15 1.314.108 1.913l-.03.408c-.038.487-.073.93-.053 1.353.026.527.136.879.333 1.112.223.263.494.428.72.528a2 2 0 0 0 .335.117l.01.002.613.109v.628h-2.402a3.3 3.3 0 0 1-.42-.415c-.509-.601-.655-1.345-.687-2.009-.025-.527.02-1.094.059-1.592l.026-.347c.044-.621.044-1.067-.049-1.377a.63.63 0 0 0-.148-.276.64.64 0 0 0-.313-.157 3 3 0 0 0-.512-.066 6 6 0 0 0-.286-.01h-.016L13.25 3.5h-.75V2h.75z"></path><path d="M13.25 16.75v.75h.035a7 7 0 0 0 .363-.014 4.6 4.6 0 0 0 .785-.109c.397-.1.738-.28 1.007-.55.268-.269.429-.587.524-.907.182-.608.15-1.314.108-1.912l-.03-.41c-.038-.486-.073-.93-.053-1.352.026-.527.136-.879.333-1.112.223-.263.494-.428.72-.528a2 2 0 0 1 .335-.117l.01-.002.613-.109V9.75h-2.402a3.3 3.3 0 0 0-.42.416c-.509.6-.655 1.344-.687 2.008-.025.527.02 1.095.059 1.592l.026.347c.044.621.044 1.067-.049 1.378a.63.63 0 0 1-.148.275.64.64 0 0 1-.313.157 3 3 0 0 1-.512.066 6 6 0 0 1-.286.01l-.016.001H12.5v1.5h.75zm-6.5-14V2h-.035a6 6 0 0 0-.363.014 4.6 4.6 0 0 0-.785.109 2.13 2.13 0 0 0-1.008.55 2.1 2.1 0 0 0-.524.907c-.181.608-.15 1.314-.108 1.913l.031.408c.038.487.073.93.052 1.353-.025.527-.136.879-.333 1.112a2 2 0 0 1-.718.528 2 2 0 0 1-.337.117l-.01.002L2 9.122v.628h2.402a3.3 3.3 0 0 0 .42-.415c.509-.601.654-1.345.686-2.009.026-.527-.019-1.094-.058-1.592q-.015-.18-.026-.347c-.044-.621-.044-1.067.048-1.377a.63.63 0 0 1 .149-.276.64.64 0 0 1 .312-.157c.13-.032.323-.054.513-.066a6 6 0 0 1 .286-.01h.015L6.75 3.5h.75V2h-.75zm0 14v.75h-.035a7 7 0 0 1-.363-.014 4.6 4.6 0 0 1-.785-.109 2.13 2.13 0 0 1-1.008-.55 2.1 2.1 0 0 1-.524-.907c-.181-.608-.15-1.314-.108-1.912l.031-.41c.038-.486.073-.93.052-1.352-.025-.527-.136-.879-.333-1.112a2 2 0 0 0-.718-.528 2 2 0 0 0-.337-.117l-.01-.002L2 10.378V9.75h2.402q.218.178.42.416c.509.6.654 1.344.686 2.008.026.527-.019 1.095-.058 1.592q-.015.18-.026.347c-.044.621-.044 1.067.048 1.378a.63.63 0 0 0 .149.275.64.64 0 0 0 .312.157c.13.032.323.054.513.066a6 6 0 0 0 .286.01l.015.001H7.5v1.5h-.75z"></path></svg>',
	};

	/**
	 * Adaptador de upload customizado para integrar com o endpoint configurado.
	 *
	 * @param {File} file Arquivo que será enviado.
	 * @returns {Promise<{default: string}>} Retorno com a URL da imagem enviada.
	 */
	class CustomUploadAdapter {
		constructor(loader, uploadUrl) {
			this.loader = loader;
			this.uploadUrl = settings.fileUploadUrl;
			this.request = null;
		}

		upload() {
			return this.loader.file.then((file) => new Promise((resolve, reject) => {
				const formData = new FormData();
				formData.append('file', file);

				this.request = $.ajax({
					url: this.uploadUrl,
					method: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					dataType: 'json'
				}).done((payload) => {
					if (payload?.success && payload.file?.url) {
						resolve({ default: payload.file.url });
					} else {
						reject('Falha ao enviar a imagem.');
					}
				}).fail(() => reject('Erro ao concluir o upload.'));
			}));
		}

		abort() {
			if (this.request?.abort) {
				this.request.abort();
			}
		}
	}

	class CustomUploadAdapterPlugin extends Plugin {
		static get pluginName() { return 'CustomUploadAdapterPlugin'; }

		init() {
			this.editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
				return new CustomUploadAdapter(loader);
			};


		}
	}

	class TemplatesPlugin extends Plugin {
		static get pluginName() { return 'TemplatesPlugin'; }

		init() {
			const editor = this.editor;

			editor.ui.componentFactory.add('Templates', (locale) => {
				const button = new ButtonView(locale);

				button.set({
					label: 'Templates',
					icon: icons.templates,
					tooltip: 'Selecionar template',
					withText: false
				});

				button.on('execute', () => this.openTemplateModal(editor));
				return button;
			});
		}

		openTemplateModal(editor) {
			const { modal, close } = createModal('Selecionar template', `
                                <div class="mb-3">
                                        <label class="form-label">Buscar</label>
                                        <input type="text" class="form-control" id="ck-template-search" placeholder="Busque pelo nome ou descrição">
                                </div>
                                <div class="row g-3">
                                        <div class="col-md-5">
                                                <div class="list-group" id="ck-template-list"></div>
                                        </div>
                                        <div class="col-md-7">
                                                <div class="border rounded p-3 bg-light" id="ck-template-preview">Selecione um template para visualizar.</div>
                                                <div class="form-check mt-3">
                                                        <input class="form-check-input" type="checkbox" id="ck-template-replace">
                                                        <label class="form-check-label" for="ck-template-replace">Substituir todo o conteúdo</label>
                                                </div>
                                        </div>
                                </div>
                        `);

			const $listElement = modal.find('#ck-template-list');
			const $preview = modal.find('#ck-template-preview');
			const $replaceToggle = modal.find('#ck-template-replace');
			let selectedHtml = '';

			const renderTemplates = (templates) => {
				$listElement.empty();

				if (!templates.length) {
					$listElement.append('<div class="text-muted px-2">Nenhum template encontrado.</div>');
					$preview.html('Selecione um template para visualizar.');
					selectedHtml = '';
					return;
				}

				templates.forEach((template) => {
					const $card = $('<button>', { type: 'button', class: 'list-group-item list-group-item-action ck-template-card' });
					$card.html(`
                                                <strong>${template.name}</strong><br>
                                                <span class="text-muted">${template.description || 'Sem descrição'}</span>
                                        `);

					$card.on('click', () => {
						$listElement.find('.ck-template-card').removeClass('active');
						$card.addClass('active');
						selectedHtml = template.html_content || '';
						$preview.html(selectedHtml || 'Sem conteúdo para exibir.');
					});

					$listElement.append($card);
				});

				const $first = $listElement.find('.ck-template-card').first();
				if ($first.length) {
					$first.trigger('click');
				}
			};

			const loadTemplates = (term = '') => {
				const url = `${settings.templateSearchUrl}?q=${encodeURIComponent(term)}`;

				fetchJson(url)
					.done((payload) => {
						if (!payload?.success) {
							$listElement.html('<div class="text-muted px-2">Não foi possível carregar os templates.</div>');
							return;
						}

						renderTemplates(payload.templates || []);
					})
					.fail(() => {
						$listElement.html('<div class="text-muted px-2">Erro ao buscar templates.</div>');
					});
			};

			modal.find('#ck-template-search').on('input', (event) => {
				loadTemplates(event.target.value || '');
			});

			modal.find('.ck-modal-confirm').on('click', () => {
				if (!selectedHtml) {
					close();
					return;
				}

				if ($replaceToggle.is(':checked')) {
					editor.setData(selectedHtml);
				} else {
					insertHtml(editor, selectedHtml);
				}

				close();
			});

			loadTemplates('');
		}
	}

	class TagsPlugin extends Plugin {
		static get pluginName() { return 'TagsPlugin'; }

		init() {
			const editor = this.editor;

			editor.ui.componentFactory.add('Tags', (locale) => {
				const dropdown = createDropdown(locale);

				dropdown.buttonView.set({
					label: 'TAG\'s',
					icon: icons.tags,
					tooltip: 'Inserir TAG',
					withText: false
				});

				const tags = [
					{ label: 'Nome', html: '{{nome}}' },
					{ label: 'E-mail', html: '{{email}}' },
                                        { label: 'Link de Visualização', html: '<a href="{{webview_link}}">Link de Visualização</a>' },
					{ label: 'Link Opt-out', html: '<a href="{{optout_link}}" target="_blank">Sair</a>' }
				];

				const items = new Collection();

				for (const tag of tags) {
					items.add({
						type: 'button',
						model: new UIModel({
							label: tag.label,
							withText: true,
							commandParam: tag.html
						})
					});
				}

				addListToDropdown(dropdown, items);

				this.listenTo(dropdown, 'execute', evt => {
					const html = evt.source.commandParam;

					// Se for só texto simples, insere como texto.
					if (!html.includes('<')) {
						editor.model.change(writer => {
							editor.model.insertContent(
								writer.createText(html)
							);
						});
					} else {
						// Para HTML: converter view -> model e inserir.
						const viewFragment = editor.data.processor.toView(html);
						const modelFragment = editor.data.toModel(viewFragment);

						editor.model.insertContent(modelFragment);
					}

					editor.editing.view.focus();
				});

				return dropdown;
			});
		}
	}

	function createModal(title, bodyHtml) {
		const modalId = `ck-modal-${Date.now()}`;
		const $modal = $(`
                        <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}-label" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">
                                                <div class="modal-header">
                                                        <h5 class="modal-title" id="${modalId}-label">${title}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                </div>
                                                <div class="modal-body">${bodyHtml}</div>
                                                <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="button" class="btn btn-primary ck-modal-confirm">Confirmar</button>
                                                </div>
                                        </div>
                                </div>
                        </div>
                `);

		$('body').append($modal);
		const modalInstance = new bootstrap.Modal($modal[0]);
		modalInstance.show();

		const close = () => {
			if ($modal[0].contains(document.activeElement)) {
				document.activeElement.blur();
			}
			$modal.on('hidden.bs.modal', () => $modal.remove());
			modalInstance.hide();
		};

		return { modal: $modal, close, modalInstance };
	}

	function insertHtml(editor, html) {
		if (!html) {
			return;
		}

		if (editor.editing?.view?.focus) {
			editor.editing.view.focus();
		}

		if (editor.model?.insertContent && editor.data?.processor?.toView) {
			const viewFragment = editor.data.processor.toView(html);
			const modelFragment = editor.data.toModel(viewFragment);
			editor.model.insertContent(modelFragment);
			return;
		}
	}

	function fetchJson(url, options = {}) {
		const hasFormData = options.body instanceof FormData;
		return $.ajax({
			url,
			method: options.method || 'GET',
			data: options.body || options.data,
			dataType: 'json',
			processData: !hasFormData,
			contentType: hasFormData ? false : undefined
		});
	}

        function removeImageSizeAttributes(html) {
                if (!html) {
                        return '';
                }

                const parser = new DOMParser();
                const documentFragment = parser.parseFromString(html, 'text/html');

                documentFragment.querySelectorAll('img').forEach((image) => {
                        image.removeAttribute('width');
                        image.removeAttribute('height');
                });

                return documentFragment.body.innerHTML;
        }

        function renderEditorPreview(targetId = 'editorPreviewContent') {
                const previewElement = document.getElementById(targetId);
                if (!previewElement) {
                        return;
                }
                const content = typeof window.getRichEditorData === 'function' ? window.getRichEditorData() : null;
                const sanitized = removeImageSizeAttributes(content || '');

                previewElement.innerHTML = sanitized || '<p class="text-muted">Nenhum conteúdo para pré-visualizar.</p>';
        }

	window.renderEditorPreview = renderEditorPreview;

	function initEditors() {
		$('#richEditor').each(function() {
			const element = this;

			ClassicEditor
				.create(element, {
					licenseKey: settings.licence,
					language: 'pt-br',
					plugins: editorPlugins,
					extraPlugins: [
						TemplatesPlugin,
						TagsPlugin,
						CustomUploadAdapterPlugin
					],
					menuBar: {
						isVisible: true
					},
					toolbar: {
						items: [
							'undo',
							'redo',
							'|',
							'sourceEditing',
							'showBlocks',
							//'textPartLanguage',
							'fullscreen',
							'|',
							'Templates', 'Tags',
							'|',
							'fontSize',
							'fontFamily',
							'fontColor',
							'fontBackgroundColor',
							'|',
							'bold',
							'italic',
							'underline',
							'strikethrough',
							'subscript',
							'superscript',
							'code',
							'|',
							'emoji',
							'horizontalLine',
							'link',
							'insertImage',
							'mediaEmbed',
							'insertTable',
							'highlight',
							'blockQuote',
							'codeBlock',
							'|',
							'alignment',
							'|',
							'bulletedList',
							'numberedList',
							'todoList',
							'outdent',
							'indent',
							'|',
							'heading',
							'style',

						],

						shouldNotGroupWhenFull: true
					},
					balloonToolbar: ['bold', 'italic', '|', 'link', '|', 'bulletedList', 'numberedList'],
					blockToolbar: [
						'fontSize',
						'fontColor',
						'fontBackgroundColor',
						'|',
						'bold',
						'italic',
						'|',
						'link',
						'insertTable',
						'|',
						'bulletedList',
						'numberedList',
						'outdent',
						'indent'
					],
					fullscreen: {
						onEnterCallback: container =>
							container.classList.add(
								'editor-container',
								'editor-container_classic-editor',
								'editor-container_include-style',
								'editor-container_include-block-toolbar',
								'editor-container_include-fullscreen',
								'main-container'
							)
					},
					title: {
						// No placeholder for the title.
						placeholder: undefined
					},
					placeholder: undefined,
					heading: {
						options: [
							{ model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
							{ model: 'heading1', view: 'h2', title: 'Heading 1', class: 'ck-heading_heading1' },
							{ model: 'heading2', view: 'h3', title: 'Heading 2', class: 'ck-heading_heading2' },
							{ model: 'heading3', view: 'h4', title: 'Heading 3', class: 'ck-heading_heading3' },
							{ model: 'heading4', view: 'h5', title: 'Heading 4', class: 'ck-heading_heading4' },
							{ model: 'heading5', view: 'h6', title: 'Heading 5', class: 'ck-heading_heading5' }
						]
					},
					fontFamily: {
						supportAllValues: true
					},
					fontSize: {
						options: [10, 12, 14, 'default', 18, 20, 22],
						supportAllValues: true
					},
					fontColor: {
						colorPicker: {
							format: 'hex'
						},
						colors: DEFAULT_HEX_COLORS
					},
					fontBackgroundColor: {
						colorPicker: {
							format: 'hex'
						},
						colors: DEFAULT_HEX_COLORS
					},
					image: {
						toolbar: [
							'imageStyle:inline',
							'imageStyle:block',
							'imageStyle:side',
							'|',
							'toggleImageCaption',
							'imageTextAlternative',
							'|',
							'resizeImage:200', 'resizeImage:500'
						], 
						resizeUnit: "px",
						resizeOptions: [
							{ name: 'resizeImage:200', value: '200', icon: 'small', label: '200px' },
							{ name: 'resizeImage:500', value: '500', icon: 'medium', label: '500px' }
						],
						upload: {
							types: ['jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg+xml']
						},
						insert: {
							integrations: ['upload', 'url']
						}
					},
					link: {
						addTargetToExternalLinks: true,
						defaultProtocol: 'https://'
					},
					list: {
						properties: {
							styles: true,
							startIndex: true,
							reversed: false
						}
					},
					table: {
						contentToolbar: [
							'tableColumn',
							'tableRow',
							'mergeTableCells',
							'tableProperties',
							'tableCellProperties',
							'toggleTableCaption'
						],
						tableCellProperties: {
							borderColors: DEFAULT_HEX_COLORS,
							backgroundColors: DEFAULT_HEX_COLORS
						},
						tableProperties: {
							borderColors: DEFAULT_HEX_COLORS,
							backgroundColors: DEFAULT_HEX_COLORS
						}
					},
					htmlSupport: {
						preserveEmptyBlocksInEditingView: true,
						allow: [
							{
								name: /^(div|table|tbody|tr|td|span|h1|h2|h3|p|a)$/,
								attributes: true,
								classes: true,
								styles: true
							},
							{
								name: 'img',
								attributes: ['src', 'alt', 'class', 'style']
							}
						]
					},
					style: {
						definitions: [
							{ name: 'Button (green)', element: 'a', classes: ['button', 'button--green'] },
							{ name: 'Button (black)', element: 'a', classes: ['button', 'button--black'] }, {
								name: 'Article category',
								element: 'h3',
								classes: ['category']
							},
							{
								name: 'Title',
								element: 'h2',
								classes: ['document-title']
							},
							{
								name: 'Subtitle',
								element: 'h3',
								classes: ['document-subtitle']
							},
							{
								name: 'Info box',
								element: 'p',
								classes: ['info-box']
							},
							{
								name: 'CTA Link Primary',
								element: 'a',
								classes: ['button', 'button--green']
							},
							{
								name: 'CTA Link Secondary',
								element: 'a',
								classes: ['button', 'button--black']
							},
							{
								name: 'Marker',
								element: 'span',
								classes: ['marker']
							},
							{
								name: 'Spoiler',
								element: 'span',
								classes: ['spoiler']
							}
						]
					}
				})
				.then((editor) => {
					editorInstance = editor;
					window.editor = editor;

					enforceEditorHeight(editor, settings.height);

					window.renderEditorPreview = renderEditorPreview;

					editorReadyDeferred.resolveReady(editor);
				})
				.catch((error) => {
					console.error('Erro ao inicializar CKEditor:', error);
					editorReadyDeferred.resolveReady(null);
				});
		});
	}

	$(document).ready(initEditors);
})(jQuery);
