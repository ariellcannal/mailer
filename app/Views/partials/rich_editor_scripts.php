<?php
/** @var string|null $editorEngine */
/** @var string|null $selector */
/** @var int|null    $height */

$editorEngine = $editorEngine ?? 'tinymce';
$selector = $selector ?? '.js-rich-editor';
$selectorJs = addslashes($selector);
$height = $height ?? 500;
?>
<?php if ($editorEngine === 'ckeditor'): ?>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        window.richEditorEngine = 'ckeditor';
        window.richEditorInstances = [];

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('<?= $selectorJs ?>').forEach(function (element) {
                ClassicEditor
                    .create(element, {
                        language: 'pt-br',
                        toolbar: [
                            'undo',
                            'redo',
                            '|',
                            'heading',
                            '|',
                            'bold',
                            'italic',
                            'underline',
                            '|',
                            'bulletedList',
                            'numberedList',
                            '|',
                            'link',
                            'blockQuote',
                            'insertTable'
                        ],
                    })
                    .then(function (editor) {
                        editor.editing.view.change(function (writer) {
                            writer.setStyle('min-height', '<?= (int) $height ?>px', editor.editing.view.document.getRoot());
                        });

                        window.richEditorInstances.push({editor: editor, element: element});
                    })
                    .catch(function (error) {
                        console.error('Erro ao inicializar CKEditor.', error);
                    });
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
    </script>
<?php endif; ?>
