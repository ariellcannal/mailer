<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $isEdit = !empty($list); ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4 text-uppercase">
            <i class="fas <?= $isEdit ? 'fa-pen' : 'fa-plus' ?> text-secondary me-2"></i>
            <?= $isEdit ? 'Editar Lista' : 'Nova Lista' ?>
        </h4>

        <?php if (session('contact_lists_error')): ?>
            <div class="alert alert-danger"><?= esc(session('contact_lists_error')) ?></div>
        <?php endif; ?>

        <form action="<?= $isEdit ? base_url('contact-lists/update/' . $list['id']) : base_url('contact-lists/store') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" name="name" class="form-control" value="<?= esc(old('name', $list['name'] ?? '')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Opcional"><?= esc(old('description', $list['description'] ?? '')) ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <a href="<?= $isEdit ? base_url('contact-lists/view/' . $list['id']) : base_url('contact-lists') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
