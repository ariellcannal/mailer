<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4"><i class="fas fa-columns"></i> Mapear Colunas</h4>

        <?php if (session('contacts_error')): ?>
            <div class="alert alert-danger"><?= esc(session('contacts_error')) ?></div>
        <?php endif; ?>

        <p class="text-muted">Identifique abaixo quais colunas correspondem ao e-mail e ao nome do contato para concluir a importação.</p>

        <form action="<?= base_url('contacts/import-process') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="temp_file" value="<?= esc($tempFile) ?>">
            <?php foreach (($selectedLists ?? []) as $listId): ?>
                <input type="hidden" name="lists[]" value="<?= (int) $listId ?>">
            <?php endforeach; ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Coluna de E-mail</label>
                    <select name="email_column" class="form-control" required>
                        <option value="">Selecione</option>
                        <?php foreach ($headers as $index => $label): ?>
                            <option value="<?= $index ?>"><?= esc($label ?: 'Coluna ' . ($index + 1)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Coluna de Nome (opcional)</label>
                    <select name="name_column" class="form-control">
                        <option value="">Nenhuma</option>
                        <?php foreach ($headers as $index => $label): ?>
                            <option value="<?= $index ?>"><?= esc($label ?: 'Coluna ' . ($index + 1)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Concluir Importação
                </button>
                <a href="<?= base_url('contacts/import') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
