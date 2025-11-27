<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="row g-0">
    <div class="col-lg-6 auth-banner d-none d-lg-block">
        <div class="auth-banner-content h-100 d-flex flex-column justify-content-between">
            <div>
                <img src="<?= base_url('assets/images/logo.png') ?>" alt="Cannal" class="img-fluid mb-4" style="max-width: 200px;">
                <h2 class="fw-bold">Cadastro autorizado</h2>
                <p class="mb-0">Crie sua credencial apenas com o e-mail permitido para acessar o painel.</p>
            </div>
            <div class="d-flex align-items-center">
                <i class="fas fa-user-check fa-2x me-3"></i>
                <div>
                    <small class="d-block">Apenas <?= esc($allowedEmail) ?> pode ser usado.</small>
                    <small class="fw-bold">Segurança e conformidade garantidas.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-1">Criar conta </h4>
            </div>
            <img src="<?= base_url('assets/images/icon.png') ?>" alt="Ícone" width="48" height="48">
        </div>

        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3 border-0">
            <div class="card-body">
                <form action="<?= base_url('register') ?>" method="POST" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect" value="<?= esc($redirectTarget) ?>">
                    <div class="col-12">
                        <label class="form-label" for="registerName">Nome completo</label>
                        <input type="text" class="form-control" id="registerName" name="name" value="<?= esc(old('name')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="registerEmail">E-mail autorizado</label>
                        <input type="email" class="form-control" id="registerEmail" name="email" value="" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="registerPassword">Senha</label>
                        <input type="password" class="form-control" id="registerPassword" name="password" minlength="8" required>
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i> Criar conta</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-grid gap-2">
            <a href="<?= base_url('login') . ($redirectTarget ? '?redirect=' . urlencode($redirectTarget) : '') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Voltar para login
            </a>
            <a href="<?= base_url('auth/google') . ($redirectTarget ? '?redirect=' . urlencode($redirectTarget) : '') ?>" class="btn btn-danger">
                <i class="fab fa-google me-2"></i> Entrar com Google
            </a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
