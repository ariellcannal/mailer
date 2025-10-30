<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4"><i class="fas fa-file-code"></i> Novo Template</h4>

        <?php if (session('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('templates/store') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" name="name" value="<?= old('name') ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="3"><?= old('description') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">HTML</label>
                <textarea name="html_content" class="form-control" rows="10" required><?= old('html_content') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Thumbnail (URL)</label>
                <input type="url" name="thumbnail" value="<?= old('thumbnail') ?>" class="form-control">
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= old('is_active') ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Template ativo</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <a href="<?= base_url('templates') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
