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
            'https://cdn.ckeditor.com/4.22.1/full-all/ckeditor.js<?= $ckeditorCacheBuster ?>'
        ];

        const editorResources = {
            templateSearchUrl: '<?= base_url('templates/search') ?>',
            fileListUrl: '<?= base_url('files/list') ?>',
            fileUploadUrl: '<?= base_url('files/upload') ?>'
        };

        const imageDropdownIcon = 'data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M1.201 1C.538 1 0 1.47 0 2.1v14.363c0 .64.534 1.037 1.186 1.037h9.494a3 3 0 0 1-.414-.287 3 3 0 0 1-1.055-2.03 3 3 0 0 1 .693-2.185l.383-.455-.02.018-3.65-3.41a.695.695 0 0 0-.957-.034L1.5 13.6V2.5h15v5.535a2.97 2.97 0 0 1 1.412.932l.088.105V2.1c0-.63-.547-1.1-1.2-1.1zm11.713 2.803a2.146 2.146 0 0 0-2.049 1.992 2.14 2.14 0 0 0 1.28 2.096 2.13 2.13 0 0 0 2.644-3.11 2.13 2.13 0 0 0-1.875-.978"/><path d="M15.522 19.1a.79.79 0 0 0 .79-.79v-5.373l2.059 2.455a.79.79 0 1 0 1.211-1.015l-3.352-3.995a.79.79 0 0 0-.995-.179.8.8 0 0 0-.299.221l-3.35 3.99a.79.79 0 1 0 1.21 1.017l1.936-2.306v5.185c0 .436.353.79.79.79"/><path d="M15.522 19.1a.79.79 0 0 0 .79-.79v-5.373l2.059 2.455a.79.79 0 1 0 1.211-1.015l-3.352-3.995a.79.79 0 0 0-.995-.179.8.8 0 0 0-.299.221l-3.35 3.99a.79.79 0 1 0 1.21 1.017l1.936-2.306v5.185c0 .436.353.79.79.79"/></svg>') ?>';
        const imageLibraryIcon = 'data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M17.5 9.303V8h-13v8.5h4.341c.191.54.457 1.044.785 1.5H2a1.5 1.5 0 0 1-1.5-1.5v-13A1.5 1.5 0 0 1 2 2h4.5a1.5 1.5 0 0 1 1.06.44L9.122 4H16a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 19 8v2.531a6 6 0 0 0-1.5-1.228M16 6.5v-1H8.5l-2-2H2v13h1V8a1.5 1.5 0 0 1 1.5-1.5z"/><path d="M14.5 19.5a5 5 0 1 1 0-10 5 5 0 0 1 0 10M15 14v-2h-1v2h-2v1h2v2h1v-2h2v-1z"/></svg>') ?>';
        const templateIcon = 'data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M3 19a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8.022a6.5 6.5 0 0 0-1.5-.709V2a.5.5 0 0 0-.5-.5H3a.5.5 0 0 0-.5.5v15a.5.5 0 0 0 .5.5h6.313c.173.534.412 1.037.709 1.5z"/><path d="M9.174 14a6.5 6.5 0 0 0-.155 1H6v-1zm.848-2a6.5 6.5 0 0 0-.524 1H4v-1zm2.012-2c-.448.283-.86.62-1.224 1H6v-1zM12 4v1H4V4zm2 3V6H6v1zm1 2V8H7v1z"/><path d="M20 15.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0M15.5 13a.5.5 0 0 0-.5.5V15h-1.5a.5.5 0 0 0 0 1H15v1.5a.5.5 0 0 0 1 0V16h1.5a.5.5 0 0 0 0-1H16v-1.5a.5.5 0 0 0-.5-.5" clip-rule="evenodd"/></svg>') ?>';
        const tagsIcon = 'data:image/svg+xml;utf8,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="9.8" r="1.5"></circle><path d="M13.25 2.75V2h.035a6 6 0 0 1 .363.014c.21.013.517.041.785.109.397.1.738.281 1.007.55s.429.587.524.907c.182.608.15 1.314.108 1.913l-.03.408c-.038.487-.073.93-.053 1.353.026.527.136.879.333 1.112.223.263.494.428.72.528a2 2 0 0 0 .335.117l.01.002.613.109v.628h-2.402a3.3 3.3 0 0 1-.42-.415c-.509-.601-.655-1.345-.687-2.009-.025-.527.02-1.094.059-1.592l.026-.347c.044-.621.044-1.067-.049-1.377a.63.63 0 0 0-.148-.276.64.64 0 0 0-.313-.157 3 3 0 0 0-.512-.066 6 6 0 0 0-.286-.01h-.016L13.25 3.5h-.75V2h.75z"></path><path d="M13.25 16.75v.75h.035a7 7 0 0 0 .363-.014 4.6 4.6 0 0 0 .785-.109c.397-.1.738-.28 1.007-.55.268-.269.429-.587.524-.907.182-.608.15-1.314.108-1.912l-.03-.41c-.038-.486-.073-.93-.053-1.352.026-.527.136-.879.333-1.112.223-.263.494-.428.72-.528a2 2 0 0 1 .335-.117l.01-.002.613-.109V9.75h-2.402a3.3 3.3 0 0 0-.42.416c-.509.6-.655 1.344-.687 2.008-.025.527.02 1.095.059 1.592l.026.347c.044.621.044 1.067-.049 1.378a.63.63 0 0 1-.148.275.64.64 0 0 1-.313.157 3 3 0 0 1-.512.066 6 6 0 0 1-.286.01l-.016.001H12.5v1.5h.75zm-6.5-14V2h-.035a6 6 0 0 0-.363.014 4.6 4.6 0 0 0-.785.109 2.13 2.13 0 0 0-1.008.55 2.1 2.1 0 0 0-.524.907c-.181.608-.15 1.314-.108 1.913l.031.408c.038.487.073.93.052 1.353-.025.527-.136.879-.333 1.112a2 2 0 0 1-.718.528 2 2 0 0 1-.337.117l-.01.002L2 9.122v.628h2.402a3.3 3.3 0 0 0 .42-.415c.509-.601.654-1.345.686-2.009.026-.527-.019-1.094-.058-1.592q-.015-.18-.026-.347c-.044-.621-.044-1.067.048-1.377a.63.63 0 0 1 .149-.276.64.64 0 0 1 .312-.157c.13-.032.323-.054.513-.066a6 6 0 0 1 .286-.01h.015L6.75 3.5h.75V2h-.75zm0 14v.75h-.035a7 7 0 0 1-.363-.014 4.6 4.6 0 0 1-.785-.109 2.13 2.13 0 0 1-1.008-.55 2.1 2.1 0 0 1-.524-.907c-.181-.608-.15-1.314-.108-1.912l.031-.41c.038-.486.073-.93.052-1.352-.025-.527-.136-.879-.333-1.112a2 2 0 0 0-.718-.528 2 2 0 0 0-.337-.117l-.01-.002L2 10.378V9.75h2.402q.218.178.42.416c.509.6.654 1.344.686 2.008.026.527-.019 1.095-.058 1.592q-.015.18-.026.347c-.044.621-.044 1.067.048 1.378a.63.63 0 0 0 .149.275.64.64 0 0 0 .312.157c.13.032.323.054.513.066a6 6 0 0 1 .286.01l.015.001H7.5v1.5h-.75z"></path></svg>') ?>';
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
                return promise.catch(function () {
                    return loadCkeditorScript(source);
                });
            }, Promise.reject());
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

        function registerImagePlugin() {
            CKEDITOR.plugins.add('customimages', {
                icons: 'image',
                init: function (editor) {
                    editor.addCommand('openImageLibraryDialog', new CKEDITOR.dialogCommand('imageLibraryDialog'));
                    editor.addCommand('openImageUrlDialog', new CKEDITOR.dialogCommand('imageUrlDialog'));

                    editor.ui.addRichCombo('ImagensDropdown', {
                        label: 'Imagens',
                        title: 'Adicionar imagem',
                        icon: imageDropdownIcon,
                        voiceLabel: 'Imagens',
                        className: 'cke_combo_button',
                        panel: {
                            css: [CKEDITOR.skin.getPath('editor')].concat(editor.config.contentsCss)
                        },
                        init: function () {
                            this.startGroup('Opções de imagem');
                            this.add('upload', 'Enviar Imagem (Upload)');
                            this.add('url', 'Inserir da URL');
                            this.add('library', 'Inserir do Banco de Imagens');
                        },
                        onClick: function (value) {
                            if (value === 'upload') {
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
                                            editor.insertHtml(`<img src="${data.file.url}" alt="Imagem enviada" style="max-width:100%;height:auto;">`);
                                        })
                                        .catch(() => alert('Não foi possível concluir o upload.'))
                                        .finally(() => { uploader.value = ''; });
                                };
                                uploader.click();
                            }

                            if (value === 'url') {
                                editor.execCommand('openImageUrlDialog');
                            }

                            if (value === 'library') {
                                editor.execCommand('openImageLibraryDialog');
                            }
                        }
                    });

                    editor.ui.addButton('BancoDeImagens', {
                        label: 'Banco de Imagens',
                        command: 'openImageLibraryDialog',
                        icon: imageLibraryIcon,
                        toolbar: 'insert'
                    });
                }
            });

            CKEDITOR.dialog.add('imageUrlDialog', function (editor) {
                return {
                    title: 'Inserir imagem via URL',
                    minWidth: 400,
                    minHeight: 120,
                    contents: [
                        {
                            id: 'urlTab',
                            elements: [
                                { type: 'text', id: 'imageUrl', label: 'URL da imagem', validate: CKEDITOR.dialog.validate.notEmpty('Informe a URL da imagem.') },
                                { type: 'text', id: 'imageAlt', label: 'Texto alternativo' }
                            ]
                        }
                    ],
                    onOk: function () {
                        const imageUrl = this.getValueOf('urlTab', 'imageUrl');
                        const altText = this.getValueOf('urlTab', 'imageAlt') || 'Imagem externa';
                        if (imageUrl) {
                            editor.insertHtml(`<img src="${imageUrl}" alt="${altText}" style="max-width:100%;height:auto;">`);
                        }
                    }
                };
            });

            CKEDITOR.dialog.add('imageLibraryDialog', function (editor) {
                let selectedImage = '';
                const listId = 'ck-image-library-list';
                const statusId = 'ck-image-library-status';

                function renderFiles(files) {
                    const container = document.getElementById(listId);
                    const status = document.getElementById(statusId);

                    selectedImage = '';
                    if (!container || !status) {
                        return;
                    }

                    if (!files.length) {
                        container.innerHTML = '<div class="ck-custom-muted">Nenhuma imagem disponível.</div>';
                        status.textContent = 'Envie uma nova imagem para começar.';
                        return;
                    }

                    const cards = files.map((file) => `
                        <div class="ck-custom-card" data-url="${file.url}">
                            <img src="${file.url}" alt="${file.name}">
                            <div class="ck-custom-muted">${file.name}</div>
                            <div class="ck-custom-muted">${(file.size / 1024).toFixed(1)} KB</div>
                        </div>
                    `).join('');

                    container.innerHTML = cards;
                    status.textContent = `${files.length} arquivo(s) no Banco de Imagens.`;

                    Array.from(container.querySelectorAll('.ck-custom-card')).forEach((card) => {
                        card.addEventListener('click', function () {
                            container.querySelectorAll('.ck-custom-card').forEach((item) => item.classList.remove('is-active'));
                            this.classList.add('is-active');
                            selectedImage = this.getAttribute('data-url') || '';
                        });
                    });
                }

                function loadFiles() {
                    const status = document.getElementById(statusId);
                    if (status) {
                        status.textContent = 'Carregando Biblioteca de Imagens...';
                    }

                    fetch(editorResources.fileListUrl)
                        .then((response) => response.json())
                        .then((data) => {
                            if (!data.success) {
                                if (status) { status.textContent = 'Não foi possível carregar o Banco de Imagens.'; }
                                return;
                            }
                            renderFiles(data.files || []);
                        })
                        .catch(() => {
                            if (status) { status.textContent = 'Erro ao carregar arquivos.'; }
                        });
                }

                return {
                    title: 'Banco de Imagens',
                    minWidth: 600,
                    minHeight: 400,
                    contents: [
                        {
                            id: 'libraryTab',
                            elements: [
                                {
                                    type: 'html',
                                    id: 'libraryHtml',
                                    html: `
                                        <div class="ck-custom-actions">
                                            <button type="button" class="cke_dialog_ui_button" id="ck-image-upload-trigger">Enviar nova imagem</button>
                                            <div id="${statusId}" class="ck-custom-muted"></div>
                                        </div>
                                        <div id="${listId}" class="ck-custom-list"></div>
                                    `
                                }
                            ]
                        }
                    ],
                    onShow: function () {
                        const trigger = document.getElementById('ck-image-upload-trigger');
                        if (trigger) {
                            trigger.onclick = function () {
                                const uploader = createHiddenUploadInput();
                                uploader.onchange = function (event) {
                                    const [file] = event.target.files;
                                    if (!file) { return; }
                                    const formData = new FormData();
                                    formData.append('file', file);
                                    fetch(editorResources.fileUploadUrl, { method: 'POST', body: formData })
                                        .then((response) => response.json())
                                        .then((data) => {
                                            if (!data.success) {
                                                alert('Não foi possível enviar a imagem.');
                                                return;
                                            }
                                            loadFiles();
                                        })
                                        .catch(() => alert('Erro ao enviar a imagem.'))
                                        .finally(() => { uploader.value = ''; });
                                };
                                uploader.click();
                            };
                        }
                        loadFiles();
                    },
                    onOk: function () {
                        if (selectedImage) {
                            editor.insertHtml(`<img src="${selectedImage}" alt="Imagem do Banco" style="max-width:100%;height:auto;">`);
                        }
                    }
                };
            });
        }

        function registerTemplatePlugin() {
            CKEDITOR.plugins.add('templateselector', {
                icons: 'template',
                init: function (editor) {
                    editor.addCommand('openTemplateDialog', new CKEDITOR.dialogCommand('templateDialog'));
                    editor.ui.addButton('Templates', {
                        label: 'Templates',
                        command: 'openTemplateDialog',
                        icon: templateIcon,
                        toolbar: 'insert'
                    });
                }
            });

            CKEDITOR.dialog.add('templateDialog', function (editor) {
                let selectedTemplate = '';
                const listId = 'ck-template-list';
                const searchId = 'ck-template-search';
                const feedbackId = 'ck-template-feedback';
                const replaceId = 'ck-template-replace';

                const debounce = (fn, delay = 300) => {
                    let timer;
                    return (...args) => {
                        clearTimeout(timer);
                        timer = setTimeout(() => fn(...args), delay);
                    };
                };

                function renderTemplates(templates) {
                    const list = document.getElementById(listId);
                    const feedback = document.getElementById(feedbackId);
                    selectedTemplate = '';

                    if (!list || !feedback) {
                        return;
                    }

                    if (!templates.length) {
                        list.innerHTML = '';
                        feedback.textContent = 'Nenhum template encontrado.';
                        return;
                    }

                    feedback.textContent = '';
                    list.innerHTML = templates.map((tpl) => `
                        <div class="ck-custom-card" data-content="${encodeURIComponent(tpl.html_content)}">
                            <div class="fw-bold">${tpl.name}</div>
                            <div class="ck-custom-muted">${tpl.description || 'Sem descrição'}</div>
                        </div>
                    `).join('');

                    Array.from(list.querySelectorAll('.ck-custom-card')).forEach((card) => {
                        card.addEventListener('click', function () {
                            list.querySelectorAll('.ck-custom-card').forEach((item) => item.classList.remove('is-active'));
                            this.classList.add('is-active');
                            const encoded = this.getAttribute('data-content') || '';
                            selectedTemplate = decodeURIComponent(encoded);
                        });
                    });
                }

                function fetchTemplates(query = '') {
                    const feedback = document.getElementById(feedbackId);
                    if (feedback) {
                        feedback.textContent = 'Buscando templates...';
                    }

                    fetch(`${editorResources.templateSearchUrl}?q=${encodeURIComponent(query)}`)
                        .then((response) => response.json())
                        .then((data) => {
                            if (!data.success) {
                                if (feedback) { feedback.textContent = 'Não foi possível carregar os templates.'; }
                                return;
                            }
                            renderTemplates(data.templates || []);
                        })
                        .catch(() => {
                            if (feedback) { feedback.textContent = 'Erro ao buscar templates.'; }
                        });
                }

                return {
                    title: 'Importar Template',
                    minWidth: 640,
                    minHeight: 360,
                    contents: [
                        {
                            id: 'templateTab',
                            elements: [
                                {
                                    type: 'html',
                                    id: 'templateHtml',
                                    html: `
                                        <div class="ck-custom-row mb-2">
                                            <input type="search" id="${searchId}" placeholder="Filtrar por nome ou descrição" class="cke_dialog_ui_input_text">
                                        </div>
                                        <div id="${feedbackId}" class="ck-custom-muted">Carregando templates...</div>
                                        <div id="${listId}" class="ck-custom-list"></div>
                                        <label class="ck-custom-row mt-2">
                                            <input type="checkbox" id="${replaceId}"> Substituir todo o conteúdo
                                        </label>
                                    `
                                }
                            ]
                        }
                    ],
                    onShow: function () {
                        const searchInput = document.getElementById(searchId);
                        if (searchInput) {
                            searchInput.oninput = debounce((event) => fetchTemplates(event.target.value), 300);
                            searchInput.value = '';
                        }
                        fetchTemplates('');
                    },
                    onOk: function () {
                        if (!selectedTemplate) {
                            return;
                        }
                        const replaceAll = document.getElementById(replaceId)?.checked;
                        if (replaceAll) {
                            editor.setData(selectedTemplate);
                        } else {
                            editor.insertHtml(selectedTemplate);
                        }
                    }
                };
            });
        }

        function registerTagsPlugin() {
            CKEDITOR.plugins.add('tagdropdown', {
                icons: 'placeholder',
                init: function (editor) {
                    editor.ui.addRichCombo('Tags', {
                        label: 'Tags',
                        title: 'Inserir tags dinâmicas',
                        icon: tagsIcon,
                        className: 'cke_combo_button',
                        panel: {
                            css: [CKEDITOR.skin.getPath('editor')].concat(editor.config.contentsCss)
                        },
                        init: function () {
                            this.startGroup('Campos');
                            this.add('{{nome}}', 'Nome');
                            this.add('{{email}}', 'E-mail');
                            this.add('{{webview_link}}', 'Link de Visualização');
                            this.add('{{optout_link}}', 'Link Opt-out');
                        },
                        onClick: function (value) {
                            editor.insertText(value);
                        }
                    });
                }
            });
        }

        function registerFullscreenPlugin() {
            CKEDITOR.plugins.add('fullscreentoggle', {
                icons: 'maximize',
                init: function (editor) {
                    editor.addCommand('toggleMaximize', {
                        exec: function (editorInstance) {
                            editorInstance.execCommand('maximize');
                        }
                    });
                    editor.ui.addButton('TelaCheia', {
                        label: 'Tela cheia',
                        command: 'toggleMaximize',
                        icon: fullscreenIcon,
                        toolbar: 'tools'
                    });
                }
            });
        }

        function initializeCkeditor() {
            if (!window.CKEDITOR) {
                console.error('CKEditor não pôde ser carregado.');
                return;
            }

            CKEDITOR.addCss(
                '.ck-custom-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;max-height:380px;overflow:auto;padding:8px;}' +
                '.ck-custom-card{border:1px solid #e0e0e0;border-radius:6px;padding:8px;cursor:pointer;transition:all .2s ease;}' +
                '.ck-custom-card:hover{border-color:#0d6efd;box-shadow:0 0 0 .2rem rgba(13,110,253,.15);}' +
                '.ck-custom-card.is-active{border-color:#0d6efd;background:#f0f6ff;}' +
                '.ck-custom-card img{width:100%;height:120px;object-fit:cover;border-radius:4px;}' +
                '.ck-custom-row{display:flex;gap:8px;align-items:center;}' +
                '.ck-custom-row input[type="text"], .ck-custom-row input[type="search"]{width:100%;}' +
                '.ck-custom-actions{display:flex;justify-content:space-between;align-items:center;margin-top:8px;}' +
                '.ck-custom-muted{color:#6c757d;font-size:12px;}'
            );

            registerImagePlugin();
            registerTemplatePlugin();
            registerTagsPlugin();
            registerFullscreenPlugin();

            document.querySelectorAll('<?= $selectorJs ?>').forEach(function (element) {
                CKEDITOR.replace(element, {
                    height: <?= (int) $height ?>,
                    language: 'pt-br',
                    allowedContent: true,
                    extraPlugins: 'customimages,templateselector,tagdropdown,fullscreentoggle,maximize',
                    removeButtons: 'Image',
                    toolbar: [
                        { name: 'document', items: ['Source'] },
                        { name: 'clipboard', items: ['Undo', 'Redo'] },
                        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'RemoveFormat'] },
                        { name: 'paragraph', items: ['NumberedList', 'BulletedList', 'Outdent', 'Indent', 'Blockquote'] },
                        { name: 'links', items: ['Link', 'Unlink'] },
                        { name: 'insert', items: ['ImagensDropdown', 'BancoDeImagens', 'Templates', 'Tags'] },
                        { name: 'tools', items: ['TelaCheia'] },
                        { name: 'styles', items: ['Format', 'Font', 'FontSize'] },
                        { name: 'colors', items: ['TextColor', 'BGColor'] },
                        { name: 'editing', items: ['Scayt'] }
                    ]
                });
            });

            CKEDITOR.on('instanceReady', function (event) {
                window.richEditorInstances.push(event.editor);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadCkeditorSequentially(ckeditorSources)
                .then(function () {
                    initializeCkeditor();
                })
                .catch(function (error) {
                    console.error('CKEditor não pôde ser carregado.', error);
                });
        });

        window.syncRichEditors = function () {
            Object.values(CKEDITOR.instances).forEach(function (instance) {
                instance.updateElement();
            });
        };

        window.insertRichText = function (text) {
            if (!window.richEditorInstances.length) {
                return;
            }
            window.richEditorInstances[0].insertText(text);
        };

        window.insertRichHtml = function (html) {
            if (!window.richEditorInstances.length) {
                return;
            }
            window.richEditorInstances[0].insertHtml(html);
        };

        window.getRichEditorData = function () {
            if (!window.richEditorInstances.length) {
                return '';
            }
            return window.richEditorInstances[0].getData();
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
