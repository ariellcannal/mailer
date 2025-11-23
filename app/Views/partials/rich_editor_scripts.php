<?php
/** @var string|null $selector */
/** @var int|null    $height */

$selector = $selector ?? '.js-rich-editor';
$height = $height ?? 500;
?>

<link rel="stylesheet" href="<?= base_url('assets/css/rich-editor.css') ?>">
<div id="richEditorConfig"
     data-selector="<?= esc($selector) ?>"
     data-height="<?= (int) $height ?>"
     data-template-search-url="<?= base_url('templates/search') ?>"
     data-file-list-url="<?= base_url('files/list') ?>"
     data-file-upload-url="<?= base_url('files/upload') ?>">
</div>
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/47.2.0/ckeditor5.css" />
<script src="https://cdn.ckeditor.com/ckeditor5/47.2.0/ckeditor5.umd.js" defer></script>
<script src="<?= base_url('assets/js/rich-editor.js') ?>" defer></script>
