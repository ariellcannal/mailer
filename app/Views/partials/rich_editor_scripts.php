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
            'https://cdn.ckeditor.com/ckeditor5/42.0.1/super-build/ckeditor.js<?= $ckeditorCacheBuster ?>',
            'https://cdn.ckeditor.com/ckeditor5/38.1.1/super-build/ckeditor.js'
        ];

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

        function initializeCkeditor() {
            if (!window.CKEDITOR || !window.CKEDITOR.ClassicEditor) {
                console.error('CKEditor super build não pôde ser carregado.');
                return;
            }

            document.querySelectorAll('<?= $selectorJs ?>').forEach(function (element) {
                CKEDITOR.ClassicEditor
                    .create(element, {
                        language: 'pt-br',
                        placeholder: 'Escreva o conteúdo do email...',
                        toolbar: {
                            items: [
                                'undo',
                                'redo',
                                '|',
                                'heading',
                                'style',
                                '|',
                                'bold',
                                'italic',
                                'underline',
                                'strikethrough',
                                'subscript',
                                'superscript',
                                'highlight',
                                'removeFormat',
                                '|',
                                'fontFamily',
                                'fontSize',
                                'fontColor',
                                'fontBackgroundColor',
                                '|',
                                'bulletedList',
                                'numberedList',
                                'outdent',
                                'indent',
                                '|',
                                'alignment',
                                'link',
                                'blockQuote',
                                'horizontalLine',
                                'insertTable',
                                'specialCharacters',
                                'htmlEmbed',
                                'sourceEditing'
                            ],
                            shouldNotGroupWhenFull: true
                        },
                        link: {
                            addTargetToExternalLinks: true,
                            defaultProtocol: 'https://',
                            decorators: {
                                toggleDownload: {
                                    mode: 'manual',
                                    label: 'Marcar como download',
                                    attributes: {
                                        download: 'download'
                                    }
                                }
                            }
                        },
                        list: {
                            properties: {
                                styles: true,
                                startIndex: true,
                                reversed: true
                            }
                        },
                        table: {
                            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableCellProperties', 'tableProperties']
                        },
                        htmlSupport: {
                            allow: [
                                {
                                    name: /.*/,
                                    attributes: true,
                                    classes: true,
                                    styles: true
                                }
                            ]
                        },
                        htmlEmbed: {
                            showPreviews: true
                        },
                        removePlugins: [
                            'CKBox',
                            'CKFinder',
                            'EasyImage',
                            'RealTimeCollaborativeComments',
                            'RealTimeCollaborativeTrackChanges',
                            'RealTimeCollaborativeRevisionHistory',
                            'PresenceList',
                            'Comments',
                            'TrackChanges',
                            'TrackChangesData',
                            'RevisionHistory',
                            'Pagination',
                            'WProofreader',
                            'MathType'
                        ]
                    })
                    .then(function (editor) {
                        editor.editing.view.change(function (writer) {
                            writer.setStyle('min-height', '<?= (int) $height ?>px', editor.editing.view.document.getRoot());
                        });

                        window.richEditorInstances.push({ editor: editor, element: element });
                    })
                    .catch(function (error) {
                        console.error('Erro ao inicializar CKEditor.', error);
                    });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadCkeditorSequentially(ckeditorSources)
                .then(function () {
                    initializeCkeditor();
                })
                .catch(function (error) {
                    console.error('CKEditor super build não pôde ser carregado.', error);
                });
        });

        window.syncRichEditors = function () {
            window.richEditorInstances.forEach(function (instance) {
                instance.element.value = instance.editor.getData();
            });
        };

        window.insertRichText = function (text) {
            if (!window.richEditorInstances.length) {
                return;
            }

            var instance = window.richEditorInstances[0];
            instance.editor.model.change(function (writer) {
                instance.editor.model.insertContent(
                    writer.createText(text),
                    instance.editor.model.document.selection
                );
            });
        };

        window.insertRichHtml = function (html) {
            if (!window.richEditorInstances.length) {
                return;
            }

            var instance = window.richEditorInstances[0];
            instance.editor.model.change(function () {
                var viewFragment = instance.editor.data.processor.toView(html);
                var modelFragment = instance.editor.data.toModel(viewFragment);
                instance.editor.model.insertContent(modelFragment, instance.editor.model.document.selection);
            });
        };

        window.getRichEditorData = function () {
            if (!window.richEditorInstances.length) {
                return '';
            }

            return window.richEditorInstances[0].editor.getData();
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
