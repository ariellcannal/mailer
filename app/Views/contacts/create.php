<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4"><i class="fas fa-user-plus"></i> Novo Contato</h4>

        <form action="<?= base_url('contacts/store') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">E-mail *</label>
                <input type="email" class="form-control" name="email" value="<?= old('email') ?>" required>
            </div>
			
			<div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" class="form-control" name="name" value="<?= old('name') ?>">
            </div>
            
            <?php if (!empty($lists)): ?>
            <div class="mb-3">
                <label class="form-label">Listas</label>
                <select name="lists[]" class="form-select select2" multiple data-placeholder="Selecione as listas">
                    <?php foreach ($lists as $list): ?>
                        <option value="<?= $list['id'] ?>" <?= in_array($list['id'], old('lists', $selectedLists ?? [])) ? 'selected' : '' ?>>
                            <?= esc($list['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <a href="<?= base_url('contacts') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= base_url('assets/js/contacts-form.js') ?>" defer></script>
<?= $this->endSection() ?>
