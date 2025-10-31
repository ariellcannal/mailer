<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4 section-title text-uppercase"><i class="fas fa-plus text-secondary me-2"></i>Novo Remetente</h4>

        <form action="<?= base_url('senders/store') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" class="form-control" name="name" value="<?= old('name') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?= old('email') ?>" required>
            </div>

            <div class="alert alert-info d-flex gap-3 align-items-center">
                <img src="<?= base_url('assets/images/icon.svg') ?>" alt="Ícone CANNAL" width="40" height="40">
                <div>
                    O remetente será registrado automaticamente na Amazon SES.
                    Após salvar, configure os registros DNS exibidos para concluir a verificação.
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <a href="<?= base_url('senders') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
