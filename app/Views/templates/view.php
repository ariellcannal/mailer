<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-file-code"></i> <?= esc($template['name']) ?></h4>
            <div class="d-flex gap-2">
                <a href="<?= base_url('templates/edit/' . $template['id']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <form action="<?= base_url('templates/delete/' . $template['id']) ?>" method="POST" onsubmit="return confirm('Deseja realmente excluir este template?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>

        <div class="mb-3">
            <span class="badge <?= (int) $template['is_active'] === 1 ? 'bg-success' : 'bg-secondary' ?>">
                <?= (int) $template['is_active'] === 1 ? 'Ativo' : 'Inativo' ?>
            </span>
        </div>

        <?php if (!empty($template['description'])): ?>
            <p class="text-muted"><?= esc($template['description']) ?></p>
        <?php endif; ?>

        <?php if (!empty($template['thumbnail'])): ?>
            <div class="mb-3">
                <img src="<?= esc($template['thumbnail']) ?>" alt="Thumbnail" class="img-fluid rounded shadow-sm">
            </div>
        <?php endif; ?>

        <h5 class="mt-4">Pré-visualização HTML</h5>
        <div class="border rounded p-3 bg-light">
            <?= $template['html_content'] ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
