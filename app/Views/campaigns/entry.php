<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $isEdit = !empty($campaign); ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4">
            <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus' ?>"></i>
            <?= $isEdit ? 'Editar Campanha' : 'Nova Campanha' ?>
        </h4>

        <form action="<?= $isEdit ? base_url('campaigns/update/' . $campaign['id']) : base_url('campaigns/store') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" class="form-control" name="name" value="<?= esc(old('name', $campaign['name'] ?? '')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea class="form-control" name="description" rows="4"><?= esc(old('description', $campaign['description'] ?? '')) ?></textarea>
            </div>

            <div class="btn-group" role="group" aria-label="Ações do formulário">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <a href="<?= base_url('campaigns') ?>" class="btn btn-neutral">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
