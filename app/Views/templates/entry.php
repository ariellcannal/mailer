<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $isEdit = !empty($template); ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4"><i class="fas fa-file-code"></i> <?= $isEdit ? 'Editar Template' : 'Novo Template' ?></h4>

        <?php if (session('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form
            id="templateForm"
            action="<?= $isEdit ? base_url('templates/update/' . $template['id']) : base_url('templates/store') ?>"
            method="POST"
            data-sync-rich-editor="true">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" name="name" value="<?= esc(old('name', $template['name'] ?? '')) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3"><?= esc(old('description', $template['description'] ?? '')) ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12">
                    <label class="form-label">Template</label>
                    <?= view('partials/rich_editor', [
                        'height' => 400,
                        'htmlContent' => $isEdit ? ($template['html_content'] ?? '') : null,
                    ]) ?>
                </div>
            </div>

            <div class="form-check form-switch mb-3">
                <?php $isActive = old('is_active', $template['is_active'] ?? 0); ?>
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Template ativo</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <a href="<?= $isEdit ? base_url('templates/view/' . $template['id']) : base_url('templates') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/template-form.js') ?>" defer></script>
<?= $this->endSection() ?>
