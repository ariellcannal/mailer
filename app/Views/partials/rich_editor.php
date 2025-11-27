<?php
/**
 * Bloco de editor rich text compartilhado entre criação e edição.
 */

/** @var int|null $height */
/** @var string|null $htmlContent */
$height = $height ?? 300;
$htmlContent = $htmlContent ?? old('html_content');
?>
<div class="row g-0 mb-3 col-12 editor-panel shadow-sm" id="editorWrapper" aria-live="polite">
	<textarea id="richEditor" name="html_content" class="form-control js-rich-editor" rows="15" required><?= esc($htmlContent) ?></textarea>
</div>
<?= $this->section('scripts') ?>
<div id="richEditorConfig" data-licence="<?= getenv('cke.licence') ?>" data-height="<?= (int) $height ?>" data-template-search-url="<?= base_url('templates/search') ?>" data-file-list-url="<?= base_url('files/list') ?>" data-file-upload-url="<?= base_url('files/upload') ?>"></div>
<link rel="stylesheet" href="<?= base_url('assets/js/ckeditor5/ckeditor5.css') ?>" />
<script src="<?= base_url('assets/js/ckeditor5/ckeditor5.umd.js') ?>"></script>
<script src="<?= base_url('assets/js/ckeditor5/translations/pt-br.js') ?>"></script>
<script src="<?= base_url('assets/js/rich-editor.js') ?>" defer></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= base_url('assets/js/messages-form.js') ?>" defer></script>
<?= $this->endSection() ?>