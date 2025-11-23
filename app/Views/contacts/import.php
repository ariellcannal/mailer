<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4"><i class="fas fa-file-upload"></i> Importar Contatos</h4>

        <?php if (session('contacts_error')): ?>
            <div class="alert alert-danger"><?= esc(session('contacts_error')) ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>Formato do arquivo:</strong><br>
            - Formatos aceitos: CSV, XLS, XLSX<br>
            - Primeira linha deve conter os cabeçalhos<br>
            - Colunas: <code>email</code> (obrigatório), <code>nome</code> (opcional)
        </div>

        <form action="<?= base_url('contacts/import-process') ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Arquivo</label>
                <input type="file" class="form-control" name="file" accept=".csv,.xls,.xlsx" required>
            </div>

            <?php if (!empty($lists)): ?>
            <div class="mb-3">
                <label class="form-label">Adicionar às listas</label>
                <select name="lists[]" class="form-select select2" multiple data-placeholder="Selecione as listas desejadas">
                    <?php foreach ($lists as $list): ?>
                        <option value="<?= $list['id'] ?>"><?= esc($list['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Os contatos importados serão associados automaticamente às listas escolhidas.</small>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Importar
            </button>
            <a href="<?= base_url('contacts') ?>" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= base_url('assets/js/contacts-form.js') ?>" defer></script>
<?= $this->endSection() ?>
