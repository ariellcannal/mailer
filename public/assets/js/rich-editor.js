(function($) {
        'use strict';

        if (typeof CKEDITOR === 'undefined') {
                console.error('CKEDITOR global is not available. Make sure ckeditor5.umd.js is loaded before rich-editor.js.');
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
                Model,
                addListToDropdown,
                ButtonView
        } = CKEDITOR;

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
                SourceEditing
        ].filter(Boolean);

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

        const icons = {
                images: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M1.201 1C.538 1 0 1.47 0 2.1v14.363c0 .64.534 1.037 1.186 1.037h9.494a3 3 0 0 1-.414-.287 3 3 0 0 1-1.055-2.03 3 3 0 0 1 .693-2.185l.383-.455-.02.018-3.65-3.41a.695.695 0 0 0-.957-.034L1.5 13.6V2.5h15v5.535a2.97 2.97 0 0 1 1.412.932l.088.105V2.1c0-.63-.547-1.1-1.2-1.1zm11.713 2.803a2.146 2.146 0 0 0-2.049 1.992 2.14 2.14 0 0 0 1.28 2.096 2.13 2.13 0 0 0 2.644-3.11 2.13 2.13 0 0 0-1.875-.978"></path><path d="M15.522 19.1a.79.79 0 0 0 .79-.79v-5.373l2.059 2.455a.79.79 0 1 0 1.211-1.015l-3.352-3.995a.79.79 0 0 0-.995-.179.8.8 0 0 0-.299.221l-3.35 3.99a.79.79 0 1 0 1.21 1.017l1.936-2.306v5.185c0 .436.353.79.79.79"></path><path d="M15.522 19.1a.79.79 0 0 0 .79-.79v-5.373l2.059 2.455a.79.79 0 1 0 1.211-1.015l-3.352-3.995a.79.79 0 0 0-.995-.179.8.8 0 0 0-.299.221l-3.35 3.99a.79.79 0 1 0 1.21 1.017l1.936-2.306v5.185c0 .436.353.79.79.79"></path></svg>',
                library: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M17.5 9.303V8h-13v8.5h4.341c.191.54.457 1.044.785 1.5H2a1.5 1.5 0 0 1-1.5-1.5v-13A1.5 1.5 0 0 1 2 2h4.5a1.5 1.5 0 0 1 1.06.44L9.122 4H16a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 19 8v2.531a6 6 0 0 0-1.5-1.228M16 6.5v-1H8.5l-2-2H2v13h1V8a1.5 1.5 0 0 1 1.5-1.5z"></path><path d="M14.5 19.5a5 5 0 1 1 0-10 5 5 0 0 1 0 10M15 14v-2h-1v2h-2v1h2v2h1v-2h2v-1z"></path></svg>',
                templates: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M3 19a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8.022a6.5 6.5 0 0 0-1.5-.709V2a.5.5 0 0 0-.5-.5H3a.5.5 0 0 0-.5.5v15a.5.5 0 0 0 .5.5h6.313c.173.534.412 1.037.709 1.5z"></path><path d="M9.174 14a6.5 6.5 0 0 0-.155 1H6v-1zm.848-2a6.5 6.5 0 0 0-.524 1H4v-1zm2.012-2c-.448.283-.86.62-1.224 1H6v-1zM12 4v1H4V4zm2 3V6H6v1zm1 2V8H7v1z"></path><path d="M20 15.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0M15.5 13a.5.5 0 0 0-.5.5V15h-1.5a.5.5 0 0 0 0 1H15v1.5a.5.5 0 0 0 1 0V16h1.5a.5.5 0 0 0 0-1H16v-1.5a.5.5 0 0 0-.5-.5" clip-rule="evenodd"></path></svg>',
                tags: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="9.8" r="1.5"></circle><path d="M13.25 2.75V2h.035a6 6 0 0 1 .363.014c.21.013.517.041.785.109.397.1.738.281 1.007.55s.429.587.524.907c.182.608.15 1.314.108 1.913l-.03.408c-.038.487-.073.93-.053 1.353.026.527.136.879.333 1.112.223.263.494.428.72.528a2 2 0 0 0 .335.117l.01.002.613.109v.628h-2.402a3.3 3.3 0 0 1-.42-.415c-.509-.601-.655-1.345-.687-2.009-.025-.527.02-1.094.059-1.592l.026-.347c.044-.621.044-1.067-.049-1.377a.63.63 0 0 0-.148-.276.64.64 0 0 0-.313-.157 3 3 0 0 0-.512-.066 6 6 0 0 0-.286-.01h-.016L13.25 3.5h-.75V2h.75z"></path><path d="M13.25 16.75v.75h.035a7 7 0 0 0 .363-.014 4.6 4.6 0 0 0 .785-.109c.397-.1.738-.28 1.007-.55.268-.269.429-.587.524-.907.182-.608.15-1.314.108-1.912l-.03-.41c-.038-.486-.073-.93-.053-1.352.026-.527.136-.879.333-1.112.223-.263.494-.428.72-.528a2 2 0 0 1 .335-.117l.01-.002.613-.109V9.75h-2.402a3.3 3.3 0 0 0-.42.416c-.509.6-.655 1.344-.687 2.008-.025.527.02 1.095.059 1.592l.026.347c.044.621.044 1.067-.049 1.378a.63.63 0 0 1-.148.275.64.64 0 0 1-.313.157 3 3 0 0 1-.512.066 6 6 0 0 1-.286.01l-.016.001H12.5v1.5h.75zm-6.5-14V2h-.035a6 6 0 0 0-.363.014 4.6 4.6 0 0 0-.785.109 2.13 2.13 0 0 0-1.008.55 2.1 2.1 0 0 0-.524.907c-.181.608-.15 1.314-.108 1.913l.031.408c.038.487.073.93.052 1.353-.025.527-.136.879-.333 1.112a2 2 0 0 1-.718.528 2 2 0 0 1-.337.117l-.01.002L2 9.122v.628h2.402a3.3 3.3 0 0 0 .42-.415c.509-.601.654-1.345.686-2.009.026-.527-.019-1.094-.058-1.592q-.015-.18-.026-.347c-.044-.621-.044-1.067.048-1.377a.63.63 0 0 1 .149-.276.64.64 0 0 1 .312-.157c.13-.032.323-.054.513-.066a6 6 0 0 1 .286-.01h.015L6.75 3.5h.75V2h-.75zm0 14v.75h-.035a7 7 0 0 1-.363-.014 4.6 4.6 0 0 1-.785-.109 2.13 2.13 0 0 1-1.008-.55 2.1 2.1 0 0 1-.524-.907c-.181-.608-.15-1.314-.108-1.912l.031-.41c.038-.486.073-.93.052-1.352-.025-.527-.136-.879-.333-1.112a2 2 0 0 0-.718-.528 2 2 0 0 0-.337-.117l-.01-.002L2 10.378V9.75h2.402q.218.178.42.416c.509.6.654 1.344.686 2.008.026.527-.019 1.095-.058 1.592q-.015.18-.026.347c-.044.621-.044 1.067.048 1.378a.63.63 0 0 0 .149.275.64.64 0 0 0 .312.157c.13.032.323.054.513.066a6 6 0 0 0 .286.01l.015.001H7.5v1.5h-.75z"></path></svg>',
                fullscreen: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M11.5 5.75a.75.75 0 0 1 0-1.5H15a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0V6.81l-2.72 2.72a.75.75 0 0 1-1.06-1.06l2.72-2.72zm-1.97 4.72a.75.75 0 0 1 0 1.06l-2.72 2.72H8.5a.75.75 0 0 1 0 1.5H5a.75.75 0 0 1-.75-.75v-3.5a.75.75 0 0 1 1.5 0v1.69l2.72-2.72a.75.75 0 0 1 1.06 0"></path><path d="M2 0h16a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2m16 1.5H2a.5.5 0 0 0-.5.5v16a.5.5 0 0 0 .5.5h16a.5.5 0 0 0 .5-.5V2a.5.5 0 0 0-.5-.5"></path></svg>'
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
                        this.uploadUrl = uploadUrl;
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
                                })
                                        .done((payload) => {
                                                if (payload?.success && payload.file?.url) {
                                                        resolve({ default: payload.file.url });
                                                } else {
                                                        reject('Falha ao enviar a imagem.');
                                                }
                                        })
                                        .fail(() => reject('Erro ao concluir o upload.'));
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
                        const fileRepository = this.editor.plugins.get('FileRepository');
                        fileRepository.createUploadAdapter = (loader) => new CustomUploadAdapter(loader, settings.fileUploadUrl);
                }
        }

        /**
         * Insere imagem no editor após o upload utilizando a API do modelo.
         *
         * @param {object} editor Instância do editor.
         * @param {File} file Arquivo selecionado.
         * @param {string} altText Texto alternativo.
         * @returns {Promise<void>} Promessa de conclusão do upload.
         */
        function uploadFileWithEditor(editor, file, altText) {
                        const fileRepository = editor.plugins.get('FileRepository');
                        const loader = fileRepository.createLoader(file);

                        if (!loader) {
                                return Promise.reject('Loader indisponível.');
                        }

                        return loader.upload().then((data) => {
                                const imageUrl = data?.default || data?.url;
                                if (!imageUrl) {
                                        return;
                                }

                                editor.model.change((writer) => {
                                        const imageElement = writer.createElement('imageInline', {
                                                src: imageUrl,
                                                alt: altText || 'Imagem enviada'
                                        });
                                        editor.model.insertContent(imageElement, editor.model.document.selection);
                                });
                        });
        }

        class ImagesDropdownPlugin extends Plugin {
                static get pluginName() { return 'ImagesDropdownPlugin'; }

                init() {
                        const editor = this.editor;

                        const addUpload = () => {
                                const $input = $('<input>', { type: 'file', accept: 'image/*', class: 'd-none' });
                                $input.on('change', (event) => {
                                        const [file] = event.target.files;
                                        if (!file) {
                                                return;
                                        }

                                        uploadFileWithEditor(editor, file, file.name)
                                                .catch((message) => alert(message || 'Falha ao enviar a imagem.'))
                                                .finally(() => $input.remove());
                                });

                                $('body').append($input);
                                $input.trigger('click');
                        };

                        const addFromUrl = () => {
                                const { modal, close } = createModal('Inserir imagem via URL', `
                                        <div class="mb-3">
                                                <label class="form-label">URL da imagem</label>
                                                <input type="url" class="form-control" id="ck-image-url" placeholder="https://...">
                                        </div>
                                        <div class="mb-3">
                                                <label class="form-label">Texto alternativo</label>
                                                <input type="text" class="form-control" id="ck-image-alt" placeholder="Descrição da imagem">
                                        </div>
                                `);

                                modal.find('.ck-modal-confirm').on('click', () => {
                                        const url = modal.find('#ck-image-url').val().trim();
                                        const alt = modal.find('#ck-image-alt').val().trim() || 'Imagem';
                                        if (url) {
                                                insertHtml(editor, `<img src="${url}" alt="${alt}" style="max-width:100%;height:auto;">`);
                                        }
                                        close();
                                });
                        };

                        const addFromLibrary = () => {
                                const { modal, close } = createModal('Banco de Imagens', `
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                                <button type="button" class="btn btn-primary" id="ck-image-upload">Enviar imagem</button>
                                                <span class="text-muted" id="ck-image-status">Carregando Banco de Imagens...</span>
                                        </div>
                                        <div class="row" id="ck-image-grid"></div>
                                `);

                                const $grid = modal.find('#ck-image-grid');
                                const $status = modal.find('#ck-image-status');
                                let selected = '';

                                const renderFiles = (files) => {
                                        $grid.empty();

                                        if (!files.length) {
                                                $status.text('Nenhuma imagem disponível.');
                                                return;
                                        }

                                        $status.text(`${files.length} arquivo(s) encontrado(s).`);

                                        files.forEach((file) => {
                                                const $card = $('<button>', { type: 'button', class: 'col-6 col-md-4 mb-3 btn btn-outline-secondary text-start ck-image-card' });
                                                $card.append(`
                                                        <div class="ratio ratio-16x9 bg-light mb-2" style="background-size: cover; background-position: center; background-image: url('${file.url}');"></div>
                                                        <div class="fw-bold d-block">${file.name}</div>
                                                        <small class="text-muted">${(file.size / 1024).toFixed(1)} KB</small>
                                                `);

                                                $card.on('click', () => {
                                                        $grid.find('.ck-image-card').removeClass('active');
                                                        $card.addClass('active');
                                                        selected = file.url;
                                                });

                                                $grid.append($card);
                                        });
                                };

                                const loadFiles = () => {
                                        $status.text('Carregando Banco de Imagens...');

                                        fetchJson(settings.fileListUrl)
                                                .done((payload) => {
                                                        if (!payload?.success) {
                                                                $status.text('Não foi possível carregar o Banco de Imagens.');
                                                                return;
                                                        }
                                                        renderFiles(payload.files || []);
                                                })
                                                .fail(() => {
                                                        $status.text('Erro ao carregar arquivos.');
                                                });
                                };

                                modal.find('#ck-image-upload').on('click', addUpload);

                                modal.find('.ck-modal-confirm').on('click', () => {
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
                                        const model = new Model();
                                        model.set({
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
                                        label: 'TAGs',
                                        icon: icons.tags,
                                        tooltip: 'Inserir TAG',
                                        withText: false
                                });

                                const tags = [
                                        { label: 'Nome', html: '{{nome}}' },
                                        { label: 'E-mail', html: '{{email}}' },
                                        { label: 'Link de Visualização', html: '<a href="{{webview_link}}" target="_blank">Link de Visualização</a>' },
                                        { label: 'Sair', html: '<a href="{{optout_link}}" target="_blank">Sair</a>' }
                                ];

                                const items = new Collection(tags.map((tag) => {
                                        const model = new Model();
                                        model.set({
                                                label: tag.label,
                                                withText: true
                                        });

                                        model.on('execute', () => insertHtml(editor, tag.html));
                                        return { type: 'button', model };
                                }));

                                addListToDropdown(dropdown, items);
                                return dropdown;
                        });
                }
        }

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

                                button.on('execute', () => toggleEditorFullscreen(editor));

                                return button;
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
                        editor.model.insertContent(modelFragment, editor.model.document?.selection);
                        return;
                }

                if (editor.__editable) {
                        editor.__editable.focus();
                        document.execCommand('insertHTML', false, html);
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

        function renderEditorPreview(targetId = 'editorPreviewContent') {
                syncEditors();
                const previewElement = document.getElementById(targetId);
                if (!previewElement) {
                        return;
                }
                const messageElement = document.getElementById('messageEditor');
                const fallback = messageElement ? messageElement.value : '';
                const content = typeof window.getRichEditorData === 'function' ? window.getRichEditorData() : fallback;
                previewElement.innerHTML = content || '<p class="text-muted">Nenhum conteúdo para pré-visualizar.</p>';
        }

        /**
         * Alterna o modo fullscreen para o editor principal e botão auxiliar.
         *
         * @param {object} editor Instância do editor CKEditor.
         * @returns {void}
         */
        function toggleEditorFullscreen(editor) {
                const instance = editor || window.editor;
                if (!instance?.ui?.getEditableElement) {
                        return;
                }

                const $wrapper = $(instance.ui.getEditableElement()).closest('.ck-editor');
                const $toggleButton = $('#editorFullscreenToggle');

                if (!$wrapper.length) {
                        return;
                }

                $wrapper.toggleClass('editor-fullscreen');
                $('body').toggleClass('ck-fullscreen');

                if ($toggleButton.length) {
                        $toggleButton.toggleClass('active');
                }
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

        window.switchEditorMode = switchEditorMode;
        window.renderEditorPreview = renderEditorPreview;
        window.toggleEditorFullscreen = toggleEditorFullscreen;
        window.syncEditors = syncEditors;

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
                                                CustomUploadAdapterPlugin
                                        ],
                                        menuBar: {
                                                isVisible: true
                                        },
                                        toolbar: {
                                                items: [
                                                        'Imagens', 'BancoImagens', 'Templates', 'Tags', 'TelaCheia',
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
                                                        'toggleImageCaption',
                                                        'imageTextAlternative',
                                                        'resizeImage:20',
                                                        'resizeImage:60',
                                                        'resizeImage:100',
                                                        '|',
                                                        'imageStyle:inline'
                                                ],
                                                resizeOptions: [
                                                        { name: 'resizeImage:20', value: '20', icon: 'small' },
                                                        { name: 'resizeImage:60', value: '60', icon: 'medium' },
                                                        { name: 'resizeImage:100', value: '100', icon: 'large' }
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
                                                        { name: 'Button (green)', element: 'a', classes: ['button', 'button--green'] },
                                                        { name: 'Button (black)', element: 'a', classes: ['button', 'button--black'] }
                                                ]
                                        }
                                })
                                .then((editor) => {
                                        window.editor = editor;

                                        /**
                                         * Recupera o conteúdo atual do editor principal.
                                         *
                                         * @returns {string}
                                         */
                                        window.getRichEditorData = function getRichEditorData() {
                                                return editor.getData();
                                        };

                                        /**
                                         * Sincroniza o conteúdo do editor com o campo de mensagem.
                                         *
                                         * @returns {void}
                                         */
                                        window.syncRichEditors = function syncRichEditors() {
                                                const messageElement = document.getElementById('messageEditor');
                                                if (messageElement) {
                                                        messageElement.value = editor.getData();
                                                }
                                        };

                                        window.switchEditorMode = switchEditorMode;
                                        window.renderEditorPreview = renderEditorPreview;
                                        window.toggleEditorFullscreen = () => toggleEditorFullscreen(editor);
                                })
                                .catch((error) => {
                                        console.error('Erro ao inicializar CKEditor:', error);
                                });
                });
        }

        $(document).ready(initEditors);
})(jQuery);
