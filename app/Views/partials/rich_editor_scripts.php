<?php
/** @var string|null $editorEngine */
/** @var string|null $selector */
/** @var int|null    $height */

$editorEngine = $editorEngine ?? 'tinymce';
$selector = $selector ?? '.js-rich-editor';
$selectorJs = addslashes($selector);
$height = $height ?? 500;
$ckeditorCacheBuster = ENVIRONMENT === 'development' ? ('?t=' . time()) : '';
?>
<?php if ($editorEngine === 'ckeditor'): ?>
    <script>
        window.richEditorEngine = 'ckeditor';
        window.richEditorInstances = [];

        const ckeditorSources = [
            'https://cdn.ckeditor.com/ckeditor5/43.2.2/super-build/ckeditor.js<?= $ckeditorCacheBuster ?>',
            'https://cdn.ckeditor.com/ckeditor5/43.2.2/super-build/translations/pt-br.js<?= $ckeditorCacheBuster ?>'
        ];

        const editorResources = {
            templateSearchUrl: '<?= base_url('templates/search') ?>',
            fileListUrl: '<?= base_url('files/list') ?>',
            fileUploadUrl: '<?= base_url('files/upload') ?>'
        };

        const imageDropdownIcon = 'data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M1.201 1C.538 1 0 1.47 0 2.1v14.363c0 .64.534 1.037 1.186 1.037h9.494a3 3 0 0 1-.414-.287 3 3 0 0 1-1.055-2.03 3 3 0 0 1 .693-2.185l.383-.455-.02.018-3.65-3.41a.695.695 0 0 0-.957-.034L1.5 13.6V2.5h15v5.535a2.97 2.97 0 0 1 1.412.932l.088.105V2.1c0-.63-.547-1.1-1.2-1.1zm11.713 2.803a2.146 2.146 0 0 0-2.049 1.992 2.14 2.14 0 0 0 1.28 2.096 2.13 2.13 0 0 0 2.644-3.11 2.13 2.13 0 0 0-1.875-.978"></path><path d="M15.522 19.1a.79.79 0 0 0 .79-.79v-5.373l2.059 2.455a.79.79 0 1 0 1.211-1.015l-3.352-3.995a.79.79 0 0 0-.995-.179.8.8 0 0 0-.299.221l-3.35 3.99a.79.79 0 1 0 1.21 1.017l1.936-2.306v5.185c0 .436.353.79.79.79"></path><path d="M15.522 19.1a.79.79 0 0 0 .79-.79v-5.373l2.059 2.455a.79.79 0 1 0 1.211-1.015l-3.352-3.995a.79.79 0 0 0-.995-.179.8.8 0 0 0-.299.221l-3.35 3.99a.79.79 0 1 0 1.21 1.017l1.936-2.306v5.185c0 .436.353.79.79.79"></path></svg>') ?>';
        const imageLibraryIcon = 'data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M17.5 9.303V8h-13v8.5h4.341c.191.54.457 1.044.785 1.5H2a1.5 1.5 0 0 1-1.5-1.5v-13A1.5 1.5 0 0 1 2 2h4.5a1.5 1.5 0 0 1 1.06.44L9.122 4H16a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 19 8v2.531a6 6 0 0 0-1.5-1.228M16 6.5v-1H8.5l-2-2H2v13h1V8a1.5 1.5 0 0 1 1.5-1.5z"></path><path d="M14.5 19.5a5 5 0 1 1 0-10 5 5 0 0 1 0 10M15 14v-2h-1v2h-2v1h2v2h1v-2h2v-1z"></path></svg>') ?>';
        const templateIcon = 'data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M3 19a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8.022a6.5 6.5 0 0 0-1.5-.709V2a.5.5 0 0 0-.5-.5H3a.5.5 0 0 0-.5.5v15a.5.5 0 0 0 .5.5h6.313c.173.534.412 1.037.709 1.5z"></path><path d="M9.174 14a6.5 6.5 0 0 0-.155 1H6v-1zm.848-2a6.5 6.5 0 0 0-.524 1H4v-1zm2.012-2c-.448.283-.86.62-1.224 1H6v-1zM12 4v1H4V4zm2 3V6H6v1zm1 2V8H7v1z"></path><path d="M20 15.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0M15.5 13a.5.5 0 0 0-.5.5V15h-1.5a.5.5 0 0 0 0 1H15v1.5a.5.5 0 0 0 1 0V16h1.5a.5.5 0 0 0 0-1H16v-1.5a.5.5 0 0 0-.5-.5" clip-rule="evenodd"></path></svg>') ?>';
        const tagsIcon = 'data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="9.8" r="1.5"></circle><path d="M13.25 2.75V2h.035a6 6 0 0 1 .363.014c.21.013.517.041.785.109.397.1.738.281 1.007.55s.429.587.524.907c.182.608.15 1.314.108 1.913l-.03.408c-.038.487-.073.93-.053 1.353.026.527.136.879.333 1.112.223.263.494.428.72.528a2 2 0 0 0 .335.117l.01.002.613.109v.628h-2.402a3.3 3.3 0 0 1-.42-.415c-.509-.601-.655-1.345-.687-2.009-.025-.527.02-1.094.059-1.592l.026-.347c.044-.621.044-1.067-.049-1.377a.63.63 0 0 0-.148-.276.64.64 0 0 0-.313-.157 3 3 0 0 0-.512-.066 6 6 0 0 0-.286-.01h-.016L13.25 3.5h-.75V2h.75z"></path><path d="M13.25 16.75v.75h.035a7 7 0 0 0 .363-.014 4.6 4.6 0 0 0 .785-.109c.397-.1.738-.28 1.007-.55.268-.269.429-.587.524-.907.182-.608.15-1.314.108-1.912l-.03-.41c-.038-.486-.073-.93-.053-1.352.026-.527.136-.879.333-1.112.223-.263.494-.428.72-.528a2 2 0 0 1 .335-.117l.01-.002.613-.109V9.75h-2.402a3.3 3.3 0 0 0-.42.416c-.509.6-.655 1.344-.687 2.008-.025.527.02 1.095.059 1.592l.026.347c.044.621.044 1.067-.049 1.378a.63.63 0 0 1-.148.275.64.64 0 0 1-.313.157 3 3 0 0 1-.512.066 6 6 0 0 1-.286.01l-.016.001H12.5v1.5h.75zm-6.5-14V2h-.035a6 6 0 0 0-.363.014 4.6 4.6 0 0 0-.785.109 2.13 2.13 0 0 0-1.008.55 2.1 2.1 0 0 0-.524.907c-.181.608-.15 1.314-.108 1.913l.031.408c.038.487.073.93.052 1.353-.025.527-.136.879-.333 1.112a2 2 0 0 1-.718.528 2 2 0 0 1-.337.117l-.01.002L2 9.122v.628h2.402a3.3 3.3 0 0 0 .42-.415c.509-.601.654-1.345.686-2.009.026-.527-.019-1.094-.058-1.592q-.015-.18-.026-.347c-.044-.621-.044-1.067.048-1.377a.63.63 0 0 1 .149-.276.64.64 0 0 1 .312-.157c.13-.032.323-.054.513-.066a6 6 0 0 1 .286-.01h.015L6.75 3.5h.75V2h-.75zm0 14v.75h-.035a7 7 0 0 1-.363-.014 4.6 4.6 0 0 1-.785-.109 2.13 2.13 0 0 1-1.008-.55 2.1 2.1 0 0 1-.524-.907c-.181-.608-.15-1.314-.108-1.912l.031-.41c.038-.486.073-.93.052-1.352-.025-.527-.136-.879-.333-1.112a2 2 0 0 0-.718-.528 2 2 0 0 0-.337-.117l-.01-.002L2 10.378V9.75h2.402q.218.178.42.416c.509.6.654 1.344.686 2.008.026.527-.019 1.095-.058 1.592q-.015.18-.026.347c-.044.621-.044 1.067.048 1.378a.63.63 0 0 0 .149.275.64.64 0 0 0 .312.157c.13.032.323.054.513.066a6 6 0 0 0 .286.01l.015.001H7.5v1.5h-.75z"></path></svg>') ?>';
        const fullscreenIcon = 'data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M11.5 5.75a.75.75 0 0 1 0-1.5H15a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0V6.81l-2.72 2.72a.75.75 0 0 1-1.06-1.06l2.72-2.72zm-1.97 4.72a.75.75 0 0 1 0 1.06l-2.72 2.72H8.5a.75.75 0 0 1 0 1.5H5a.75.75 0 0 1-.75-.75v-3.5a.75.75 0 0 1 1.5 0v1.69l2.72-2.72a.75.75 0 0 1 1.06 0"></path><path d="M2 0h16a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2m16 1.5H2a.5.5 0 0 0-.5.5v16a.5.5 0 0 0 .5.5h16a.5.5 0 0 0 .5-.5V2a.5.5 0 0 0-.5-.5"></path></svg>') ?>';

        function loadCkeditorScript(source) {
            return new Promise(function (resolve, reject) {
                const script = document.createElement('script');
                script.src = source;
                script.onload = function () { resolve(source); };
                script.onerror = function () { reject(new Error('Falha ao carregar ' + source)); };
                document.head.appendChild(script);
            });
        }

        function loadCkeditorSequentially(sources) {
            return sources.reduce(function (promise, source) {
                return promise.then(function () { return loadCkeditorScript(source); });
            }, Promise.resolve());
        }

        function createHiddenUploadInput() {
            let uploader = document.getElementById('ckeditorHiddenUploader');
            if (!uploader) {
                uploader = document.createElement('input');
                uploader.type = 'file';
                uploader.accept = 'image/*';
                uploader.id = 'ckeditorHiddenUploader';
                uploader.style.display = 'none';
                document.body.appendChild(uploader);
            }
            return uploader;
        }

        function createCkModal(title, bodyHtml, { showCancel = true } = {}) {
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
                        ${showCancel ? '<button type="button" class="ck ck-button ck-button_with-text ck-modal__cancel">Cancelar</button>' : ''}
                        <button type="button" class="ck ck-button ck-button_with-text ck-button_action ck-modal__confirm">Confirmar</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';

            function close() {
                modal.remove();
                document.body.style.overflow = '';
            }

            return { modal, close };
        }

        function insertHtml(editor, html) {
            const viewFragment = editor.data.processor.toView(html);
            const modelFragment = editor.data.toModel(viewFragment);
            editor.model.insertContent(modelFragment, editor.model.document.selection);
        }

        function resolveCkeditorApi() {
            const root = window.CKEDITOR || {};
            const core = root.core || {};
            const ui = root.ui || {};
            const utils = root.utils || {};
            const dropdownUtils = ui.dropdownUtils || {};
            const button = ui.button || {};

            const Plugin = core.Plugin || root.Plugin;
            const ButtonView = button.ButtonView || ui.ButtonView;
            const createDropdown = dropdownUtils.createDropdown;
            const addListToDropdown = dropdownUtils.addListToDropdown;
            const Model = ui.Model || (button.ButtonView ? button.ButtonView.Model : undefined) || root.Model;
            const Collection = utils.Collection || root.Collection;

            if (!Plugin || !ButtonView || !createDropdown || !addListToDropdown || !Model || !Collection) {
                console.error('CKEditor 5 não pôde ser inicializado: dependências indisponíveis.');
                return null;
            }

            return { Plugin, ButtonView, createDropdown, addListToDropdown, Model, Collection };
        }

        function registerPlugins() {
            const api = resolveCkeditorApi();

            if (!api) {
                return;
            }

            const { Plugin, ButtonView, createDropdown, addListToDropdown, Model, Collection } = api;

            class ImageToolsPlugin extends Plugin {
                static get pluginName() { return 'ImageToolsPlugin'; }

                init() {
                    const editor = this.editor;

                    const openImageUrlDialog = () => {
                        const body = `
                            <div class="ck-form-row">
                                <label class="ck-form-label">URL da imagem</label>
                                <input type="url" class="ck ck-input ck-input_focused" id="ck-image-url" placeholder="https://...">
                            </div>
                            <div class="ck-form-row">
                                <label class="ck-form-label">Texto alternativo</label>
                                <input type="text" class="ck ck-input" id="ck-image-alt" placeholder="Descrição opcional">
                            </div>
                        `;
                        const { modal, close } = createCkModal('Inserir imagem via URL', body);
                        modal.querySelector('.ck-modal__confirm').addEventListener('click', function () {
                            const url = modal.querySelector('#ck-image-url').value.trim();
                            const alt = modal.querySelector('#ck-image-alt').value.trim() || 'Imagem externa';
                            if (url) {
                                insertHtml(editor, `<img src="${url}" alt="${alt}" style="max-width:100%;height:auto;">`);
                            }
                            close();
                        });
                        modal.querySelector('.ck-modal__cancel')?.addEventListener('click', close);
                        modal.querySelector('.ck-modal__backdrop').addEventListener('click', close);
                    };

                    const renderFileGrid = (files, container, status) => {
                        container.innerHTML = '';
                        status.textContent = files.length ? `${files.length} arquivo(s) no Banco de Imagens.` : 'Nenhuma imagem disponível.';

                        if (!files.length) {
                            return;
                        }

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
                            card.dataset.url = file.url;
                            container.appendChild(card);
                        });
                    };

                    const openImageLibraryDialog = () => {
                        const body = `
                            <div class="ck-library-actions">
                                <button type="button" class="ck ck-button ck-button_with-text ck-button_action" id="ck-image-upload-trigger">Enviar imagem</button>
                                <div class="ck-text-muted" id="ck-image-library-status">Carregando Biblioteca de Imagens...</div>
                            </div>
                            <div class="ck-image-grid" id="ck-image-library-list"></div>
                        `;
                        const { modal, close } = createCkModal('Banco de Imagens', body);
                        const container = modal.querySelector('#ck-image-library-list');
                        const status = modal.querySelector('#ck-image-library-status');
                        let selectedUrl = '';

                        const hydrateEvents = () => {
                            container.querySelectorAll('.ck-image-card').forEach((card) => {
                                card.addEventListener('click', function () {
                                    container.querySelectorAll('.ck-image-card').forEach((item) => item.classList.remove('is-active'));
                                    this.classList.add('is-active');
                                    selectedUrl = this.dataset.url || '';
                                });
                            });
                        };

                        const fetchFiles = () => {
                            status.textContent = 'Carregando Biblioteca de Imagens...';
                            fetch(editorResources.fileListUrl)
                                .then((response) => response.json())
                                .then((data) => {
                                    if (!data.success) {
                                        status.textContent = 'Não foi possível carregar o Banco de Imagens.';
                                        return;
                                    }
                                    renderFileGrid(data.files || [], container, status);
                                    hydrateEvents();
                                })
                                .catch(() => { status.textContent = 'Erro ao carregar arquivos.'; });
                        };

                        modal.querySelector('#ck-image-upload-trigger').addEventListener('click', function () {
                            const uploader = createHiddenUploadInput();
                            uploader.onchange = function (event) {
                                const [file] = event.target.files;
                                if (!file) { return; }
                                const formData = new FormData();
                                formData.append('file', file);
                                status.textContent = 'Enviando imagem...';
                                fetch(editorResources.fileUploadUrl, { method: 'POST', body: formData })
                                    .then((response) => response.json())
                                    .then((data) => {
                                        if (!data.success) {
                                            status.textContent = 'Falha ao enviar a imagem.';
                                            return;
                                        }
                                        fetchFiles();
                                    })
                                    .catch(() => { status.textContent = 'Não foi possível concluir o upload.'; })
                                    .finally(() => { uploader.value = ''; });
                            };
                            uploader.click();
                        });

                        modal.querySelector('.ck-modal__confirm').addEventListener('click', function () {
                            if (selectedUrl) {
                                insertHtml(editor, `<img src="${selectedUrl}" alt="Imagem do Banco" style="max-width:100%;height:auto;">`);
                            }
                            close();
                        });

                        modal.querySelector('.ck-modal__cancel')?.addEventListener('click', close);
                        modal.querySelector('.ck-modal__backdrop').addEventListener('click', close);
                        fetchFiles();
                    };

                    const imagemDropdownFactory = (locale) => {
                        const dropdown = createDropdown(locale);
                        dropdown.buttonView.set({
                            label: 'Imagens',
                            icon: imageDropdownIcon,
                            tooltip: 'Adicionar imagem'
                        });

                        const options = [
                            { id: 'upload', label: 'Enviar Imagem (Upload)' },
                            { id: 'url', label: 'Inserir da URL' },
                            { id: 'library', label: 'Inserir do Banco de Imagens' }
                        ];

                        const dropdownItems = new Collection();

                        options.forEach((option) => {
                            const model = new Model({ label: option.label, withText: true });
                            model.on('execute', () => {
                                if (option.id === 'upload') {
                                    const uploader = createHiddenUploadInput();
                                    uploader.onchange = function (event) {
                                        const [file] = event.target.files;
                                        if (!file) { return; }
                                        const formData = new FormData();
                                        formData.append('file', file);
                                        fetch(editorResources.fileUploadUrl, { method: 'POST', body: formData })
                                            .then((response) => response.json())
                                            .then((data) => {
                                                if (!data.success || !data.file?.url) {
                                                    alert('Falha ao enviar a imagem.');
                                                    return;
                                                }
                                                insertHtml(editor, `<img src="${data.file.url}" alt="Imagem enviada" style="max-width:100%;height:auto;">`);
                                            })
                                            .catch(() => alert('Não foi possível concluir o upload.'))
                                            .finally(() => { uploader.value = ''; });
                                    };
                                    uploader.click();
                                }

                                if (option.id === 'url') {
                                    openImageUrlDialog();
                                }

                                if (option.id === 'library') {
                                    openImageLibraryDialog();
                                }
                            });
                            dropdownItems.add({ type: 'button', model });
                        });

                        addListToDropdown(dropdown, dropdownItems);
                        return dropdown;
                    };

                    this.editor.ui.componentFactory.add('ImagensDropdown', imagemDropdownFactory);

                    this.editor.ui.componentFactory.add('BancoDeImagens', (locale) => {
                        const view = new ButtonView(locale);
                        view.set({
                            label: 'Banco de Imagens',
                            icon: imageLibraryIcon,
                            tooltip: true
                        });
                        view.on('execute', openImageLibraryDialog);
                        return view;
                    });
                }
            }

            class TemplateSelectorPlugin extends Plugin {
                static get pluginName() { return 'TemplateSelectorPlugin'; }

                init() {
                    const editor = this.editor;
                    this.editor.ui.componentFactory.add('Templates', (locale) => {
                        const button = new ButtonView(locale);
                        button.set({
                            label: 'Templates',
                            icon: templateIcon,
                            tooltip: true
                        });
                        button.on('execute', () => this.showTemplateDialog(editor));
                        return button;
                    });
                }

                showTemplateDialog(editor) {
                    const body = `
                        <p class="ck-text-muted">Busque pelo nome ou descrição e visualize o conteúdo antes de inserir.</p>
                        <div class="ck-form-row">
                            <label class="ck-form-label">Buscar template</label>
                            <input type="search" class="ck ck-input" id="ck-template-search" placeholder="Filtrar por nome ou descrição">
                        </div>
                        <div class="ck-template-layout">
                            <div class="ck-template-list" id="ck-template-list"></div>
                            <div class="ck-template-preview" id="ck-template-preview">
                                <div class="ck-text-muted">Selecione um template para visualizar.</div>
                            </div>
                        </div>
                        <label class="ck-form-row ck-template-replace">
                            <input type="checkbox" id="ck-template-replace" class="form-check-input"> Substituir todo o conteúdo
                        </label>
                    `;

                    const { modal, close } = createCkModal('Importar Template', body);
                    const listEl = modal.querySelector('#ck-template-list');
                    const previewEl = modal.querySelector('#ck-template-preview');
                    const searchEl = modal.querySelector('#ck-template-search');
                    const replaceEl = modal.querySelector('#ck-template-replace');
                    let selectedHtml = '';

                    const renderList = (templates) => {
                        listEl.innerHTML = '';
                        if (!templates.length) {
                            listEl.innerHTML = '<div class="ck-text-muted">Nenhum template encontrado.</div>';
                            previewEl.innerHTML = '<div class="ck-text-muted">Nada para exibir.</div>';
                            selectedHtml = '';
                            return;
                        }

                        templates.forEach((tpl) => {
                            const textPreview = (tpl.html_content || '')
                                .replace(/<[^>]+>/g, ' ')
                                .replace(/\s+/g, ' ')
                                .trim()
                                .slice(0, 140);

                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'ck-template-card ck ck-button ck-button_with-text';
                            item.innerHTML = `
                                <div class="ck-template-title">${tpl.name}</div>
                                <div class="ck-text-muted">${tpl.description || 'Sem descrição'}</div>
                                <div class="ck-template-snippet">${textPreview || 'Prévia indisponível'}</div>
                            `;
                            item.addEventListener('click', function () {
                                listEl.querySelectorAll('.ck-template-card').forEach((el) => el.classList.remove('is-active'));
                                this.classList.add('is-active');
                                selectedHtml = tpl.html_content || '';
                                previewEl.innerHTML = tpl.html_content || '<div class="ck-text-muted">Sem conteúdo para exibir.</div>';
                            });
                            item.addEventListener('dblclick', function () {
                                listEl.querySelector('.ck-modal__confirm')?.click();
                            });
                            listEl.appendChild(item);
                        });
                    };

                    const fetchTemplates = (query = '') => {
                        previewEl.innerHTML = '<div class="ck-text-muted">Carregando...</div>';
                        fetch(`${editorResources.templateSearchUrl}?q=${encodeURIComponent(query)}`)
                            .then((response) => response.json())
                            .then((data) => {
                                if (!data.success) {
                                    listEl.innerHTML = '<div class="ck-text-muted">Não foi possível carregar os templates.</div>';
                                    previewEl.innerHTML = '<div class="ck-text-muted">Erro ao carregar.</div>';
                                    return;
                                }
                                renderList(data.templates || []);
                                if (data.templates?.length) {
                                    const first = listEl.querySelector('.ck-template-card');
                                    first?.click();
                                }
                            })
                            .catch(() => {
                                listEl.innerHTML = '<div class="ck-text-muted">Erro ao buscar templates.</div>';
                                previewEl.innerHTML = '<div class="ck-text-muted">Erro ao buscar templates.</div>';
                            });
                    };

                    modal.querySelector('.ck-modal__confirm').addEventListener('click', function () {
                        if (!selectedHtml) {
                            close();
                            return;
                        }
                        if (replaceEl.checked) {
                            editor.setData(selectedHtml);
                        } else {
                            insertHtml(editor, selectedHtml);
                        }
                        close();
                    });

                    modal.querySelector('.ck-modal__cancel')?.addEventListener('click', close);
                    modal.querySelector('.ck-modal__backdrop').addEventListener('click', close);
                    searchEl.addEventListener('input', function (event) {
                        fetchTemplates(event.target.value);
                    });

                    fetchTemplates('');
                }
            }

            class TagDropdownPlugin extends Plugin {
                static get pluginName() { return 'TagDropdownPlugin'; }

                init() {
                    const editor = this.editor;

                    editor.ui.componentFactory.add('Tags', (locale) => {
                        const dropdown = createDropdown(locale);
                        dropdown.buttonView.set({ label: 'Tags', icon: tagsIcon, tooltip: 'Inserir tags dinâmicas' });

                        const items = [
                            { value: '{{nome}}', label: 'Nome', html: '{{nome}}' },
                            { value: '{{email}}', label: 'E-mail', html: '{{email}}' },
                            { value: '<a href="{{webview_link}}">Link de Visualização</a>', label: 'Link de Visualização', html: '<a href="{{webview_link}}">Link de Visualização</a>' },
                            { value: '<a href="{{optout_link}}">Link Opt-out</a>', label: 'Link Opt-out', html: '<a href="{{optout_link}}">Link Opt-out</a>' }
                        ];

                        const definitions = new Collection();

                        items.forEach((item) => {
                            const model = new Model({ label: item.label, withText: true });
                            model.on('execute', () => insertHtml(editor, item.html));
                            definitions.add({ type: 'button', model });
                        });

                        addListToDropdown(dropdown, definitions);
                        return dropdown;
                    });
                }
            }

            class FullscreenTogglePlugin extends Plugin {
                static get pluginName() { return 'FullscreenTogglePlugin'; }

                init() {
                    const editor = this.editor;
                    this.editor.ui.componentFactory.add('TelaCheia', (locale) => {
                        const button = new ButtonView(locale);
                        button.set({ label: 'Tela cheia', icon: fullscreenIcon, tooltip: true });
                        button.on('execute', () => {
                            const host = editor.ui.view.element.closest('.ck-editor');
                            host?.classList.toggle('ck-editor--fullscreen');
                            document.body.classList.toggle('ck-editor-body-lock', host?.classList.contains('ck-editor--fullscreen'));
                        });
                        return button;
                    });
                }
            }

            window.CustomCkeditorPlugins = [ImageToolsPlugin, TemplateSelectorPlugin, TagDropdownPlugin, FullscreenTogglePlugin];
        }

        function initializeCkeditor() {
            const EditorConstructor = window.CKEDITOR?.ClassicEditor || window.ClassicEditor;

            if (!EditorConstructor) {
                console.error('CKEditor 5 não pôde ser carregado.');
                return;
            }

            registerPlugins();

            const styles = document.createElement('style');
            styles.textContent = `
                .ck-editor__editable_inline { min-height: <?= (int) $height ?>px; }
                .ck-modal { position: fixed; inset: 0; z-index: 1050; display: flex; align-items: center; justify-content: center; }
                .ck-modal__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.35); }
                .ck-modal__dialog { position: relative; background: #fff; border-radius: 12px; box-shadow: 0 6px 30px rgba(0,0,0,.15); padding: 16px; width: min(1140px, 96vw); max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
                .ck-modal__header { margin-bottom: 12px; }
                .ck-modal__title { margin: 0; font-size: 18px; }
                .ck-modal__body { overflow: auto; flex: 1 1 auto; padding: 8px 0; }
                .ck-modal__footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
                .ck-form-row { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
                .ck-form-label { font-weight: 600; }
                .ck-image-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); grid-auto-rows: 1fr; gap: 12px; max-height: 420px; overflow: auto; align-items: stretch; }
                .ck-image-card { text-align: left; height: 260px; padding: 0; border: 1px solid #e4e4e4; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; align-items: stretch; }
                .ck-image-card__thumb { flex: 1 1 auto; background-size: cover; background-position: center; min-height: 160px; }
                .ck-image-card__meta { padding: 10px; display: flex; flex-direction: column; gap: 4px; align-items: flex-start; width: 100%; }
                .ck-image-card.is-active { outline: 2px solid #0d6efd; }
                .ck-text-muted { color: #6c757d; font-size: 12px; }
                .ck-library-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
                .ck-template-layout { display: grid; grid-template-columns: minmax(260px, 360px) 1fr; gap: 12px; max-height: 500px; }
                .ck-template-list { border: 1px solid #e4e4e4; border-radius: 8px; padding: 8px; overflow: auto; max-height: 500px; display: flex; flex-direction: column; gap: 8px; }
                .ck-template-card { text-align: left; width: 100%; border: 1px solid transparent; justify-content: flex-start; }
                .ck-template-card.is-active { border-color: #0d6efd; box-shadow: 0 0 0 0.2rem rgba(13,110,253,.15); }
                .ck-template-title { font-weight: 600; margin-bottom: 4px; }
                .ck-template-preview { border: 1px solid #e4e4e4; border-radius: 8px; padding: 12px; overflow: auto; background: #fafafa; min-height: 200px; }
                .ck-template-replace { gap: 8px; align-items: center; }
                .ck-template-snippet { font-size: 12px; color: #495057; text-align: left; margin-top: 4px; line-height: 1.3; }
                .ck-editor--fullscreen { position: fixed !important; inset: 0; z-index: 1200; background: #fff; padding: 16px; }
                .ck-editor--fullscreen .ck-editor__editable { min-height: calc(100vh - 120px); }
                .ck-editor-body-lock { overflow: hidden; }
            `;
            document.head.appendChild(styles);

            document.querySelectorAll('<?= $selectorJs ?>').forEach(function (element) {
                EditorConstructor.create(element, {
                    language: 'pt-br',
                    extraPlugins: window.CustomCkeditorPlugins,
                    toolbar: {
                        items: [
                            'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', '|',
                            'bulletedList', 'numberedList', 'outdent', 'indent', '|', 'link', '|',
                            'ImagensDropdown', 'BancoDeImagens', 'Templates', 'Tags', '|', 'blockQuote', 'insertTable', '|', 'TelaCheia'
                        ]
                    },
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Parágrafo', class: 'ck-heading_paragraph' },
                            { model: 'heading2', view: 'h2', title: 'Título 2', class: 'ck-heading_heading2' },
                            { model: 'heading3', view: 'h3', title: 'Título 3', class: 'ck-heading_heading3' },
                            { model: 'heading4', view: 'h4', title: 'Título 4', class: 'ck-heading_heading4' }
                        ]
                    }
                })
                    .then(function (editor) {
                        window.richEditorInstances.push(editor);
                    })
                    .catch(function (error) {
                        console.error('Erro ao inicializar o CKEditor 5', error);
                    });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadCkeditorSequentially(ckeditorSources)
                .then(function () { initializeCkeditor(); })
                .catch(function (error) { console.error('CKEditor 5 não pôde ser carregado.', error); });
        });

        window.syncRichEditors = function () {
            window.richEditorInstances.forEach(function (instance) { instance.updateSourceElement?.(); });
        };

        window.insertRichText = function (text) {
            window.insertRichHtml(text);
        };

        window.insertRichHtml = function (html) {
            const editor = window.richEditorInstances[0];
            if (!editor) { return; }
            insertHtml(editor, html);
        };

        window.getRichEditorData = function () {
            const editor = window.richEditorInstances[0];
            return editor ? editor.getData() : '';
        };
    </script>
<?php else: ?>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        window.richEditorEngine = 'tinymce';
        window.richEditorInstances = [];

        document.addEventListener('DOMContentLoaded', function () {
            tinymce.init({
                selector: '<?= $selectorJs ?>',
                language: 'pt_BR',
                height: <?= (int) $height ?>,
                menubar: true,
                plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime table paste help wordcount',
                toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code',
                setup: function (editor) {
                    window.richEditorInstances.push(editor);
                },
                init_instance_callback: function (editor) {
                    editor.on('remove', function () {
                        window.richEditorInstances = window.richEditorInstances.filter(function (item) {
                            return item !== editor;
                        });
                    });
                }
            });
        });

        window.syncRichEditors = function () {
            tinymce.triggerSave();
        };

        window.insertRichText = function (text) {
            window.insertRichHtml(text);
        };

        window.insertRichHtml = function (html) {
            var editor = tinymce.activeEditor || window.richEditorInstances[0];

            if (editor) {
                editor.insertContent(html);
            }
        };

        window.getRichEditorData = function () {
            var editor = tinymce.activeEditor || window.richEditorInstances[0];

            if (!editor) {
                return '';
            }

            return editor.getContent();
        };
    </script>
<?php endif; ?>
