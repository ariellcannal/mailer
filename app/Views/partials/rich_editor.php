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
	<!-- Abas Create/Preview -->
	<ul class="nav nav-tabs" id="editorTabs" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link active" id="create-tab" data-bs-toggle="tab" data-bs-target="#create-panel" type="button" role="tab" aria-controls="create-panel" aria-selected="true">
				<i class="bi bi-pencil-square"></i> Edição
			</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview-panel" type="button" role="tab" aria-controls="preview-panel" aria-selected="false">
				<i class="bi bi-eye"></i> Visualização
			</button>
		</li>
	</ul>
	
	<!-- Conteúdo das abas -->
	<div class="tab-content" id="editorTabsContent">
		<!-- Aba Create -->
		<div class="tab-pane fade show active" id="create-panel" role="tabpanel" aria-labelledby="create-tab">
			<textarea id="richEditor" name="html_content" class="form-control js-rich-editor" rows="15"><?= esc($htmlContent) ?></textarea>
		</div>
		
		<!-- Aba Preview -->
		<div class="tab-pane fade" id="preview-panel" role="tabpanel" aria-labelledby="preview-tab">
			<div class="preview-wrapper" style="padding: 20px; background: #f5f5f5;">
				<iframe id="emailPreviewFrame" style="width: 100%; min-height: 600px; border: 1px solid #ddd; background: white;"></iframe>
			</div>
		</div>
	</div>
</div>
<?= $this->section('scripts') ?>
<div id="richEditorConfig" data-licence="<?= getenv('cke.licence') ?>" data-height="<?= (int) $height ?>" data-template-search-url="<?= base_url('templates/search') ?>" data-file-list-url="<?= base_url('files/list') ?>" data-file-upload-url="<?= base_url('files/upload') ?>"></div>
<?= $this->endSection() ?>