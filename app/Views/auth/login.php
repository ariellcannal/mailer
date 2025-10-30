<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="text-center mb-4"><i class="fas fa-user-circle"></i> Acessar painel</h4>

                <?php if (session('error')): ?>
                    <div class="alert alert-danger"><?= esc(session('error')) ?></div>
                <?php endif; ?>
                <?php if (session('success')): ?>
                    <div class="alert alert-success"><?= esc(session('success')) ?></div>
                <?php endif; ?>

                <p class="text-muted">Autenticação por senha ainda não está configurada. Utilize o login social ou entre em contato com o administrador.</p>

                <button class="btn btn-danger w-100" onclick="alert('Integração com Google pendente de configuração.');">
                    <i class="fab fa-google"></i> Entrar com Google
                </button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
