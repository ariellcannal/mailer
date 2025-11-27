<?php
/**
 * Bloco de editor rich text compartilhado entre criação e edição.
 */

/** @var int|null $height */
/** @var string|null $htmlContent */

$height = $height ?? 500;
$htmlContent = $htmlContent ?? old('html_content');
?>
<div class="row g-3 mb-3" id="editorWrapper" aria-live="polite">
    <div class="col-12">
        <div id="editorCreatePanel" class="editor-panel card shadow-sm">
            <textarea id="richEditor" name="html_content" class="form-control js-rich-editor" rows="15" required><?= esc($htmlContent) ?></textarea>
        </div>

        <div id="editorPreviewPanel" class="editor-panel card shadow-sm d-none bg-light">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Preview</h6>
                <span class="badge bg-secondary">Leitura somente</span>
            </div>
            <div id="editorPreviewContent" class="border rounded p-3 bg-white" style="min-height: 400px;"></div>
            
        </div>
    </div>
</div>
<?= $this->section('scripts') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/rich-editor.css') ?>">
<div id="richEditorConfig"
     data-licence="<?= getenv('cke.licence') ?>"
     data-height="<?= (int) $height ?>"
     data-template-search-url="<?= base_url('templates/search') ?>"
     data-file-list-url="<?= base_url('files/list') ?>"
     data-file-upload-url="<?= base_url('files/upload') ?>">
</div>
<link rel="stylesheet" href="<?= base_url('assets/js/ckeditor5/ckeditor5.css') ?>" />
<script src="<?= base_url('assets/js/ckeditor5/ckeditor5.umd.js') ?>"></script>
<script src="<?= base_url('assets/js/rich-editor.js') ?>" defer></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= base_url('assets/js/messages-form.js') ?>" defer></script>
<?= $this->endSection() ?>