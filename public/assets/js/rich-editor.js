(function() {
	'use strict';

	// Garante que o bundle UMD do CKEditor está carregado.
	if (typeof CKEDITOR === 'undefined') {
		console.error('CKEDITOR global is not available. Make sure ckeditor5.umd.js is loaded before rich-editor.js.');
		return;
	}

	const DEFAULT_HEX_COLORS = [
		{
			color: '#000000',
			label: 'Black'
		},
		{
			color: '#4D4D4D',
			label: 'Dim grey'
		},
		{
			color: '#999999',
			label: 'Grey'
		},
		{
			color: '#E6E6E6',
			label: 'Light grey'
		},
		{
			color: '#FFFFFF',
			label: 'White',
			hasBorder: true
		},
		{
			color: '#E65C5C',
			label: 'Red'
		},
		{
			color: '#E69C5C',
			label: 'Orange'
		},
		{
			color: '#E6E65C',
			label: 'Yellow'
		},
		{
			color: '#C2E65C',
			label: 'Light green'
		},
		{
			color: '#5CE65C',
			label: 'Green'
		},
		{
			color: '#5CE6A6',
			label: 'Aquamarine'
		},
		{
			color: '#5CE6E6',
			label: 'Turquoise'
		},
		{
			color: '#5CA6E6',
			label: 'Light blue'
		},
		{
			color: '#5C5CE6',
			label: 'Blue'
		},
		{
			color: '#A65CE6',
			label: 'Purple'
		}
	];

	// Importa do bundle UMD (equivalente aos imports do index.js da demo).
	const {
		ClassicEditor,
		AdjacentListsSupport,
		Alignment,
		AutoImage,
		Autoformat,
		AutoLink,
		BlockQuote,
		Bold,
		Code,
		CodeBlock,
		CloudServices,
		Clipboard,
		EmptyBlock,
		Essentials,
		FontBackgroundColor,
		FontColor,
		FontFamily,
		FontSize,
		GeneralHtmlSupport,
		Heading,
		HorizontalLine,
		ImageCaption,
		ImageStyle,
		ImageToolbar,
		ImageUpload,
		Indent,
		IndentBlock,
		Italic,
		Link,
		List,
		Mention,
		Paragraph,
		PictureEditing,
		Strikethrough,
		Style,
		Table,
		TableCaption,
		TableLayout,
		TableProperties,
		TableToolbar,
		TextTransformation,
		Underline,
		ImageInline,
		Plugin,
		SourceEditing,
		createDropdown,
		Collection,
		UIModel,
		addListToDropdown,
		ButtonView
	} = CKEDITOR;

	// Plugins padrão do editor (similar ao email-editing, mas sem recursos premium).
	const editorPlugins = [
		AdjacentListsSupport,
		Alignment,
		AutoImage,
		Autoformat,
		AutoLink,
		BlockQuote,
		Bold,
		CloudServices,
		Clipboard,
		Code,
		CodeBlock,
		EmptyBlock,
		Essentials,
		FontBackgroundColor,
		FontColor,
		FontFamily,
		FontSize,
		GeneralHtmlSupport,
		Heading,
		HorizontalLine,
		ImageInline,
		ImageCaption,
		ImageStyle,
		ImageToolbar,
		ImageUpload,
		Indent,
		IndentBlock,
		Italic,
		Link,
		List,
		Mention,
		Paragraph,
		PictureEditing,
		Strikethrough,
		Style,
		Table,
		TableCaption,
		TableLayout,
		TableProperties,
		TableToolbar,
		TextTransformation,
		Underline,
		SourceEditing,
	].filter(Boolean);

	// Configurações vindas do HTML (div#richEditorConfig).
	const configElement = document.getElementById('richEditorConfig');
	if (!configElement) {
		return;
	}

	const settings = {
		licence: configElement.dataset.licence || 'GPL',
		height: Number(configElement.dataset.height || 600),
		templateSearchUrl: configElement.dataset.templateSearchUrl || '',
		fileListUrl: configElement.dataset.fileListUrl || '',
		fileUploadUrl: configElement.dataset.fileUploadUrl || ''
	};

	// SVGs customizados (como na demo).
	const icons = {
		images: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M1.201 1C.538 1 0 1.47 0 2.1v14.363c0 .64.534 1.037 1.186 1.037h9.494a3 3 0 0 1-.414-.287 3 3 0 0 1-1.055-2.03 3 3 0 0 1 .693-2.185l.383-.455-.02.018-3.65-3.41a.695.695 0 0 0-.957-.034L1.5 13.6V2.5h15v5.535a2.97 2.97 0 0 1 1.412.932l.088.105V2.1c0-.63-.547-1.1-1.2-1.1zm11.713 2.803a2.146 2.146 0 0 0-2.049 1.992 2.14 2.14 0 0 0 1.28 2.096 2.13 2.13 0 0 0 2.644-3.11 2.13 2.13 0 0 0-1.875-.978"></path><path d="M15.522 19.1a.79.79 0 0 0 .79-.79v-5.373l2.059 2.455a.79.79 0 1 0 1.211-1.015l-3.352-3.995a.79.79 0 0 0-.995-.179.8.8 0 0 0-.299.221l-3.35 3.99a.79.79 0 1 0 1.21 1.017l1.936-2.306v5.185c0 .436.353.79.79.79"></path><path d="M15.522 19.1a.79.79 0 0 0 .79-.79v-5.373l2.059 2.455a.79.79 0 1 0 1.211-1.015l-3.352-3.995a.79.79 0 0 0-.995-.179.8.8 0 0 0-.299.221l-3.35 3.99a.79.79 0 1 0 1.21 1.017l1.936-2.306v5.185c0 .436.353.79.79.79"></path></svg>',
		library: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M17.5 9.303V8h-13v8.5h4.341c.191.54.457 1.044.785 1.5H2a1.5 1.5 0 0 1-1.5-1.5v-13A1.5 1.5 0 0 1 2 2h4.5a1.5 1.5 0 0 1 1.06.44L9.122 4H16a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 19 8v2.531a6 6 0 0 0-1.5-1.228M16 6.5v-1H8.5l-2-2H2v13h1V8a1.5 1.5 0 0 1 1.5-1.5z"></path><path d="M14.5 19.5a5 5 0 1 1 0-10 5 5 0 0 1 0 10M15 14v-2h-1v2h-2v1h2v2h1v-2h2v-1z"></path></svg>',
		templates: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M3 19a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8.022a6.5 6.5 0 0 0-1.5-.709V2a.5.5 0 0 0-.5-.5H3a.5.5 0 0 0-.5.5v15a.5.5 0 0 0 .5.5h6.313c.173.534.412 1.037.709 1.5z"></path><path d="M9.174 14a6.5 6.5 0 0 0-.155 1H6v-1zm.848-2a6.5 6.5 0 0 0-.524 1H4v-1zm2.012-2c-.448.283-.86.62-1.224 1H6v-1zM12 4v1H4V4zm2 3V6H6v1zm1 2V8H7v1z"></path><path d="M20 15.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0M15.5 13a.5.5 0 0 0-.5.5V15h-1.5a.5.5 0 0 0 0 1H15v1.5a.5.5 0 0 0 1 0V16h1.5a.5.5 0 0 0 0-1H16v-1.5a.5.5 0 0 0-.5-.5" clip-rule="evenodd"></path></svg>',
		tags: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="9.8" r="1.5"></circle><path d="M13.25 2.75V2h.035a6 6 0 0 1 .363.014c.21.013.517.041.785.109.397.1.738.281 1.007.55s.429.587.524.907c.182.608.15 1.314.108 1.913l-.03.408c-.038.487-.073.93-.053 1.353.026.527.136.879.333 1.112.223.263.494.428.72.528a2 2 0 0 0 .335.117l.01.002.613.109v.628h-2.402a3.3 3.3 0 0 1-.42-.415c-.509-.601-.655-1.345-.687-2.009-.025-.527.02-1.094.059-1.592l.026-.347c.044-.621.044-1.067-.049-1.377a.63.63 0 0 0-.148-.276.64.64 0 0 0-.313-.157 3 3 0 0 0-.512-.066 6 6 0 0 0-.286-.01h-.016L13.25 3.5h-.75V2h.75z"></path><path d="M13.25 16.75v.75h.035a7 7 0 0 0 .363-.014 4.6 4.6 0 0 0 .785-.109c.397-.1.738-.28 1.007-.55.268-.269.429-.587.524-.907.182-.608.15-1.314.108-1.912l-.03-.41c-.038-.486-.073-.93-.053-1.352.026-.527.136-.879.333-1.112.223-.263.494-.428.72-.528a2 2 0 0 1 .335-.117l.01-.002.613-.109V9.75h-2.402a3.3 3.3 0 0 0-.42.416c-.509.6-.655 1.344-.687 2.008-.025.527.02 1.095.059 1.592l.026.347c.044.621.044 1.067-.049 1.378a.63.63 0 0 1-.148.275.64.64 0 0 1-.313.157 3 3 0 0 1-.512.066 6 6 0 0 1-.286.01l-.016.001H12.5v1.5h.75zm-6.5-14V2h-.035a6 6 0 0 0-.363.014 4.6 4.6 0 0 0-.785.109 2.13 2.13 0 0 0-1.008.55 2.1 2.1 0 0 0-.524.907c-.181.608-.15 1.314-.108 1.913l.031.408c.038.487.073.93.052 1.353-.025.527-.136.879-.333 1.112a2 2 0 0 1-.718.528 2 2 0 0 1-.337.117l-.01.002L2 9.122v.628h2.402a3.3 3.3 0 0 0 .42-.415c.509-.601.654-1.345.686-2.009.026-.527-.019-1.094-.058-1.592q-.015-.18-.026-.347c-.044-.621-.044-1.067.048-1.377a.63.63 0 0 1 .149-.276.64.64 0 0 1 .312-.157c.13-.032.323-.054.513-.066a6 6 0 0 1 .286-.01h.015L6.75 3.5h.75V2h-.75zm0 14v.75h-.035a7 7 0 0 1-.363-.014 4.6 4.6 0 0 1-.785-.109 2.13 2.13 0 0 1-1.008-.55 2.1 2.1 0 0 1-.524-.907c-.181-.608-.15-1.314-.108-1.912l.031-.41c.038-.486.073-.93.052-1.352-.025-.527-.136-.879-.333-1.112a2 2 0 0 0-.718-.528 2 2 0 0 0-.337-.117l-.01-.002L2 10.378V9.75h2.402q.218.178.42.416c.509.6.654 1.344.686 2.008.026.527-.019 1.095-.058 1.592q-.015.18-.026.347c-.044.621-.044 1.067.048 1.378a.63.63 0 0 0 .149.275.64.64 0 0 0 .312.157c.13.032.323.054.513.066a6 6 0 0 0 .286.01l.015.001H7.5v1.5h-.75z"></path></svg>',
		fullscreen: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M11.5 5.75a.75.75 0 0 1 0-1.5H15a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0V6.81l-2.72 2.72a.75.75 0 0 1-1.06-1.06l2.72-2.72zm-1.97 4.72a.75.75 0 0 1 0 1.06l-2.72 2.72H8.5a.75.75 0 0 1 0 1.5H5a.75.75 0 0 1-.75-.75v-3.5a.75.75 0 0 1 1.5 0v1.69l2.72-2.72a.75.75 0 0 1 1.06 0"></path><path d="M2 0h16a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2m16 1.5H2a.5.5 0 0 0-.5.5v16a.5.5 0 0 0 .5.5h16a.5.5 0 0 0 .5-.5V2a.5.5 0 0 0-.5-.5"></path></svg>',
		preview: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path d="M51.8,25.1C47.1,15.6,37.3,9,26,9S4.9,15.6,0.2,25.1c-0.3,0.6-0.3,1.3,0,1.8C4.9,36.4,14.7,43,26,43 s21.1-6.6,25.8-16.1C52.1,26.3,52.1,25.7,51.8,25.1z M26,37c-6.1,0-11-4.9-11-11s4.9-11,11-11s11,4.9,11,11S32.1,37,26,37z"></path> <path d="M26,19c-3.9,0-7,3.1-7,7s3.1,7,7,7s7-3.1,7-7S29.9,19,26,19z"></path> </g> </g></svg>'
	};

	// === Inicialização dos editores ===
	function initEditors() {
		$('#richEditor').each(function() {
			const element = this;

			ClassicEditor
				.create(element, {
					licenseKey: settings.licence,
					language: 'pt-br',
					plugins: editorPlugins,
					extraPlugins: [
						ImagesDropdownPlugin,
						ImageLibraryPlugin,
						TemplatesPlugin,
						TagsPlugin,
						FullscreenPlugin,
						CustomPreviewPlugin
					],
					menuBar: {
						isVisible: true
					},
					toolbar: {
						items: [
							'Imagens', 'BancoImagens', 'Templates', 'Tags', 'TelaCheia', 'CustomPreviewPlugin',
							'|',
							'undo',
							'redo',
							'|',
							'heading',
							'|',
							{
								label: 'Font styles',
								icon: 'text',
								items: [
									'fontSize',
									'fontFamily',
									'fontColor',
									'fontBackgroundColor'
								]
							},
							'|',
							'bold',
							'italic',
							'underline',
							'strikethrough',
							'|',
							'link',
							'insertImage',
							'insertTable',
							'insertTableLayout',
							'|',
							'alignment',
							'|',
							'bulletedList',
							'numberedList',
							'|',
							'outdent',
							'indent',
							'|',
							'sourceEditing',
							'style'
						],
						shouldNotGroupWhenFull: true
					},
					heading: {
						options: [
							{
								model: 'paragraph',
								title: 'Paragraph',
								class: 'ck-heading_paragraph'
							},
							{
								model: 'heading1',
								view: 'h2',
								title: 'Heading 1',
								class: 'ck-heading_heading1'
							},
							{
								model: 'heading2',
								view: 'h3',
								title: 'Heading 2',
								class: 'ck-heading_heading2'
							},
							{
								model: 'heading3',
								view: 'h4',
								title: 'Heading 3',
								class: 'ck-heading_heading3'
							},
							{
								model: 'heading4',
								view: 'h5',
								title: 'Heading 4',
								class: 'ck-heading_heading4'
							},
							{
								model: 'heading5',
								view: 'h6',
								title: 'Heading 5',
								class: 'ck-heading_heading5'
							}
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
							'toggleImageCaption',
							'imageTextAlternative',
							'resizeImage:20',
							'resizeImage:60',
							'resizeImage:100',
							'|',
							'imageStyle:inline'
						],
						resizeOptions: [
							{
								name: 'resizeImage:20',
								value: '20',
								icon: 'small'
							},
							{
								name: 'resizeImage:60',
								value: '60',
								icon: 'medium'
							},
							{
								name: 'resizeImage:100',
								value: '100',
								icon: 'large'
							}
						],
						upload: {
							types: ['jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg+xml']
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
								name: /^(div|table|tbody|tr|td|span|img|h1|h2|h3|p|a)$/,
								attributes: true,
								classes: true,
								styles: true
							}
						]
					},
					style: {
						definitions: [
							{
								name: 'Button (green)',
								element: 'a',
								classes: ['button', 'button--green']
							},
							{
								name: 'Button (black)',
								element: 'a',
								classes: ['button', 'button--black']
							}
						]
					},
				})
				.then((editor) => {
					window.editor = editor;
				})
				.catch((error) => {
					console.error('Erro ao inicializar CKEditor:', error);
				});
		});
	}

	/* PLUGINS CUSTOMIZADOS */

	// Dropdown "Imagens" (upload, URL, Banco).
	class ImagesDropdownPlugin extends Plugin {
		static get pluginName() { return 'ImagesDropdownPlugin'; }

		init() {
			const editor = this.editor;

			const addUpload = () => {
				const fileInput = document.createElement('input');
				fileInput.type = 'file';
				fileInput.accept = 'image/*';

				fileInput.onchange = (event) => {
					const [file] = event.target.files;
					if (!file) {
						return;
					}

					const data = new FormData();
					data.append('file', file);

					fetchJson(settings.fileUploadUrl, { method: 'POST', body: data })
						.then((payload) => {
							if (payload.success && payload.file?.url) {
								insertHtml(editor, `<img src="${payload.file.url}" alt="Imagem enviada" style="max-width:100%;height:auto;">`);
							} else {
								alert('Falha ao enviar a imagem.');
							}
						})
						.catch(() => alert('Erro ao concluir o upload.'));
				};

				fileInput.click();
			};

			const addFromUrl = () => {
				const { modal, close } = createModal('Inserir imagem via URL', `
						<div class="ck-form-row">
							<label class="ck-form-label">URL da imagem</label>
							<input type="url" class="ck ck-input" id="ck-image-url" placeholder="https://...">
						</div>
						<div class="ck-form-row">
							<label class="ck-form-label">Texto alternativo</label>
							<input type="text" class="ck ck-input" id="ck-image-alt" placeholder="Descrição da imagem">
						</div>
					`);

				modal.querySelector('.ck-modal__confirm').addEventListener('click', () => {
					const url = modal.querySelector('#ck-image-url').value.trim();
					const alt = modal.querySelector('#ck-image-alt').value.trim() || 'Imagem';
					if (url) {
						insertHtml(editor, `<img src="${url}" alt="${alt}" style="max-width:100%;height:auto;">`);
					}
					close();
				});
			};

			const addFromLibrary = () => {
				const body = `
						<div class="ck-library-actions">
							<button type="button" class="ck ck-button ck-button_with-text ck-button_action" id="ck-image-upload">Enviar imagem</button>
							<div class="ck-text-muted" id="ck-image-status">Carregando Banco de Imagens...</div>
						</div>
						<div class="ck-image-grid" id="ck-image-grid"></div>
					`;

				const { modal, close } = createModal('Banco de Imagens', body);
				const grid = modal.querySelector('#ck-image-grid');
				const status = modal.querySelector('#ck-image-status');
				let selected = '';

				const renderFiles = (files) => {
					grid.innerHTML = '';

					if (!files.length) {
						status.textContent = 'Nenhuma imagem disponível.';
						return;
					}

					status.textContent = `${files.length} arquivo(s) encontrado(s).`;

					files.forEach((file) => {
						const card = document.createElement('button');
						card.type = 'button';
						card.className = 'ck-image-card ck ck-button ck-button_with-text';
						card.innerHTML = `
								<div class="ck-image-card__thumb" style="background-image:url('${file.url}')"></div>
								<div class="ck-image-card__meta">
									<span class="fw-bold">${file.name}</span>
									<span class="ck-text-muted">${(file.size / 1024).toFixed(1)} KB</span>
								</div>
							`;

						card.addEventListener('click', () => {
							grid.querySelectorAll('.ck-image-card').forEach((item) => item.classList.remove('is-active'));
							card.classList.add('is-active');
							selected = file.url;
						});

						grid.appendChild(card);
					});
				};

				const loadFiles = () => {
					status.textContent = 'Carregando Banco de Imagens...';

					fetchJson(settings.fileListUrl)
						.then((payload) => {
							if (!payload.success) {
								status.textContent = 'Não foi possível carregar o Banco de Imagens.';
								return;
							}
							renderFiles(payload.files || []);
						})
						.catch(() => {
							status.textContent = 'Erro ao carregar arquivos.';
						});
				};

				modal.querySelector('#ck-image-upload').addEventListener('click', addUpload);

				modal.querySelector('.ck-modal__confirm').addEventListener('click', () => {
					if (selected) {
						insertHtml(editor, `<img src="${selected}" alt="Imagem do Banco" style="max-width:100%;height:auto;">`);
					}
					close();
				});

				loadFiles();
			};

			editor.ui.componentFactory.add('Imagens', (locale) => {
				const dropdown = createDropdown(locale);

				dropdown.buttonView.set({
					label: 'Imagens',
					icon: icons.images,
					tooltip: 'Inserir imagem',
					withText: false
				});

				const items = new Collection([
					{ label: 'Enviar imagem (Upload)', action: addUpload },
					{ label: 'Inserir da URL', action: addFromUrl },
					{ label: 'Inserir do Banco de Imagens', action: addFromLibrary }
				].map((item) => {
					const model = new UIModel({
						label: item.label,
						withText: true
					});

					model.on('execute', item.action);

					return { type: 'button', model };
				}));

				addListToDropdown(dropdown, items);

				return dropdown;
			});
		}
	}

	// Botão simples para abrir o Banco de Imagens “externo”.
	class ImageLibraryPlugin extends Plugin {
		static get pluginName() { return 'ImageLibraryPlugin'; }

		init() {
			const editor = this.editor;

			editor.ui.componentFactory.add('BancoImagens', (locale) => {
				const button = new ButtonView(locale);

				button.set({
					label: 'Banco de Imagens',
					icon: icons.library,
					tooltip: 'Abrir Banco de Imagens',
					withText: false
				});

				button.on('execute', () => {
					if (typeof editor.__openImageLibrary === 'function') {
						editor.__openImageLibrary();
					}
				});

				return button;
			});
		}
	}

	// Templates (carregados via AJAX, como na demo).
	class TemplatesPlugin extends Plugin {
		static get pluginName() { return 'TemplatesPlugin'; }

		init() {
			const editor = this.editor;

			editor.ui.componentFactory.add('Templates', (locale) => {
				const button = new ButtonView(locale);

				button.set({
					label: 'Templates',
					icon: icons.templates,
					tooltip: 'Inserir template',
					withText: false
				});

				button.on('execute', () => this.openTemplateModal(editor));

				return button;
			});
		}

		openTemplateModal(editor) {
			const body = `
					<div class="mb-3">
						<input type="search" class="ck ck-input" id="ck-template-search" placeholder="Busque pelo nome ou descrição">
					</div>
					<div class="row g-3">
						<div class="col-md-5">
							<div class="ck-template-list" id="ck-template-list"></div>
						</div>
						<div class="col-md-7">
							<div class="ck-template-preview" id="ck-template-preview">Selecione um template para visualizar.</div>
							<div class="form-check mt-3">
								<input class="form-check-input" type="checkbox" id="ck-template-replace">
								<label class="form-check-label" for="ck-template-replace">Substituir todo o conteúdo</label>
							</div>
						</div>
					</div>
				`;

			const { modal, close } = createModal('Selecionar template', body);
			const listElement = modal.querySelector('#ck-template-list');
			const preview = modal.querySelector('#ck-template-preview');
			const replaceToggle = modal.querySelector('#ck-template-replace');
			let selectedHtml = '';

			const renderTemplates = (templates) => {
				listElement.innerHTML = '';

				if (!templates.length) {
					listElement.innerHTML = '<div class="ck-text-muted">Nenhum template encontrado.</div>';
					preview.innerHTML = 'Selecione um template para visualizar.';
					selectedHtml = '';
					return;
				}

				templates.forEach((template) => {
					const card = document.createElement('button');
					card.type = 'button';
					card.className = 'ck-template-card ck ck-button ck-button_with-text';
					card.innerHTML = `
							<strong>${template.name}</strong><br>
							<span class="ck-text-muted">${template.description || 'Sem descrição'}</span>
						`;

					card.addEventListener('click', () => {
						listElement.querySelectorAll('.ck-template-card').forEach((item) => item.classList.remove('is-active'));
						card.classList.add('is-active');
						selectedHtml = template.html_content || '';
						preview.innerHTML = selectedHtml || 'Sem conteúdo para exibir.';
					});

					listElement.appendChild(card);
				});

				const first = listElement.querySelector('.ck-template-card');
				first?.click();
			};

			const loadTemplates = (term = '') => {
				const url = `${settings.templateSearchUrl}?q=${encodeURIComponent(term)}`;

				fetchJson(url)
					.then((payload) => {
						if (!payload.success) {
							listElement.innerHTML = '<div class="ck-text-muted">Não foi possível carregar os templates.</div>';
							return;
						}

						renderTemplates(payload.templates || []);
					})
					.catch(() => {
						listElement.innerHTML = '<div class="ck-text-muted">Erro ao buscar templates.</div>';
					});
			};

			modal.querySelector('#ck-template-search').addEventListener('input', (event) => {
				loadTemplates(event.target.value || '');
			});

			modal.querySelector('.ck-modal__confirm').addEventListener('click', () => {
				if (!selectedHtml) {
					close();
					return;
				}

				if (replaceToggle.checked) {
					editor.setData(selectedHtml);
				} else {
					insertHtml(editor, selectedHtml);
				}

				close();
			});

			loadTemplates('');
		}
	}

	// TAGs (merge fields) – parecido com o exemplo de placeholders da doc.
	class TagsPlugin extends Plugin {
		static get pluginName() { return 'TagsPlugin'; }

		init() {
			const editor = this.editor;

			editor.ui.componentFactory.add('Tags', (locale) => {
				const dropdown = createDropdown(locale);

				dropdown.buttonView.set({
					label: 'TAGs',
					icon: icons.tags,
					tooltip: 'Inserir TAG',
					withText: false
				});

				const tags = [
					{ label: 'Nome', html: '{{nome}}' },
					{ label: 'E-mail', html: '{{email}}' },
					{ label: 'Link de Visualização', html: '<a href="{{webview_link}}" target="_blank">Link de Visualização</a>' },
					{ label: 'Link Opt-out', html: '<a href="{{optout_link}}" target="_blank">Link Opt-out</a>' }
				];

				const items = new Collection(
					tags.map((tag) => {
						const model = new UIModel({
							label: tag.label,
							withText: true
						});

						model.on('execute', () => insertHtml(editor, tag.html));

						return { type: 'button', model };
					})
				);

				addListToDropdown(dropdown, items);

				return dropdown;
			});
		}
	}

	// Tela cheia (CSS precisa de .ck-editor--fullscreen + .ck-editor-body-lock).
	class FullscreenPlugin extends Plugin {
		static get pluginName() { return 'FullscreenPlugin'; }

		init() {
			const editor = this.editor;

			editor.ui.componentFactory.add('TelaCheia', (locale) => {
				const button = new ButtonView(locale);

				button.set({
					label: 'Tela cheia',
					icon: icons.fullscreen,
					tooltip: 'Expandir editor',
					withText: false
				});

				button.on('execute', () => {
					const wrapper = editor.ui.getEditableElement()?.closest('.ck-editor');
					const toggleButton = document.getElementById('editorFullscreenToggle');
					if (!wrapper || !toggleButton) {
						return;
					}
					wrapper.classList.toggle('editor-fullscreen');
					toggleButton.classList.toggle('active');
					document.body.classList.toggle('ck-fullscreen');
				});
				return button;
			});
		}
	}

	// Preview
	class CustomPreviewPlugin extends Plugin {
		static get pluginName() { return 'CustomPreviewPlugin'; }

		init() {
			const editor = this.editor;

			editor.ui.componentFactory.add('PreVisualizar', (locale) => {
				const button = new ButtonView(locale);

				button.set({
					label: 'Pré-visualizar',
					icon: icons.preview,
					tooltip: 'Pré-visualizar',
					withText: false
				});

				button.on('execute', () => {
					const wrapper = editor.ui.getEditableElement()?.closest('.ck-editor');
					if (!wrapper) {
						return;
					}

					wrapper.classList.toggle('ck-editor--preview');
					document.body.classList.toggle('ck-editor-body-lock', wrapper.classList.contains('ck-editor--preview'));
				});

				return button;
			});
		}
	}

	// Cria modal "estilo demo".
	function createModal(title, bodyHtml) {
		const modal = document.createElement('div');
		modal.className = 'ck ck-reset_all ck-rounded-corners ck-modal';
		modal.innerHTML = `
			<div class="ck-modal__backdrop"></div>
			<div class="ck-modal__dialog" role="dialog" aria-label="${title}">
				<div class="ck-modal__header">
					<h3 class="ck-modal__title">${title}</h3>
				</div>
				<div class="ck-modal__body">${bodyHtml}</div>
				<div class="ck-modal__footer">
					<button type="button" class="ck ck-button ck-button_with-text ck-modal__cancel">Cancelar</button>
					<button type="button" class="ck ck-button ck-button_with-text ck-button_action ck-modal__confirm">Confirmar</button>
				</div>
			</div>
		`;
		document.body.appendChild(modal);
		document.body.classList.add('ck-editor-body-lock');

		function close() {
			modal.remove();
			document.body.classList.remove('ck-editor-body-lock');
		}

		modal.querySelector('.ck-modal__backdrop').addEventListener('click', close);
		modal.querySelector('.ck-modal__cancel').addEventListener('click', close);

		return { modal, close };
	}

	// Insere HTML no editor (seguindo o padrão da demo).
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
			editor.model.insertContent(modelFragment, editor.model.document?.selection);
			return;
		}

		if (editor.__editable) {
			editor.__editable.focus();
			document.execCommand('insertHTML', false, html);
		}
	}

	function fetchJson(url, options = {}) {
		return fetch(url, options).then((response) => response.json());
	}

	function renderEditorPreview() {
		syncEditors();
		const previewElement = document.getElementById('editorPreviewContent');
		if (!previewElement) {
			return;
		}
		const messageElement = document.getElementById('messageEditor');
		const fallback = messageElement ? messageElement.value : '';
		const content = typeof window.getRichEditorData === 'function' ? window.getRichEditorData() : fallback;
		previewElement.innerHTML = content || '<p class="text-muted">Nenhum conteúdo para pré-visualizar.</p>';
	}

	function toggleEditorFullscreen() {

	}

	function switchEditorMode(mode) {
		if (mode !== 'create' && mode !== 'preview') {
			return;
		}
		if (mode === 'preview') {
			renderEditorPreview();
		}
		document.getElementById('editorModeCreate')?.classList.toggle('active', mode === 'create');
		document.getElementById('editorModePreview')?.classList.toggle('active', mode === 'preview');
		document.getElementById('editorCreatePanel')?.classList.toggle('d-none', mode !== 'create');
		document.getElementById('editorPreviewPanel')?.classList.toggle('d-none', mode !== 'preview');
	}

	function syncEditors() {
		if (typeof window.syncRichEditors === 'function') {
			window.syncRichEditors();
		}
	}

	$(document).ready(initEditors);
})();
