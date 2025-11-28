<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="row g-0">
    <div class="col-lg-6 auth-banner d-none d-lg-block">
        <div class="auth-banner-content h-100 d-flex flex-column justify-content-between">
            <div>
                <img src="<?= base_url('assets/images/logo.png') ?>" alt="Cannal" class="img-fluid mb-4" style="max-width: 200px;">
                <h2 class="fw-bold">Bem-vindo de volta</h2>
                <p class="mb-0">Acesse o painel seguro para gerenciar campanhas e entregabilidade com rapidez.</p>
            </div>
            <div class="d-flex align-items-center">
                <i class="fas fa-shield-alt fa-2x me-3"></i>
                <div>
                    <small class="d-block">Autenticação protegida</small>
                    <small class="fw-bold">Somente usuários autorizado.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-1">CANNAL Mailing</h4>
                <small class="text-muted">Acesso ao painel</small>
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
                <form action="<?= base_url('login') ?>" method="POST" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect" value="<?= esc($redirectTarget) ?>">
                    <div class="col-12">
                        <label class="form-label" for="loginEmail">E-mail</label>
                        <input type="email" class="form-control" id="loginEmail" name="email" value="<?= esc(old('email')) ?>" required>
                    </div>
                <div class="col-12">
                    <label class="form-label d-flex justify-content-between" for="loginPassword">
                        <span>Senha</span>
                        <a href="#forgotCard" data-bs-toggle="collapse" class="small">Esqueci minha senha</a>
                    </label>
                    <input type="password" class="form-control" id="loginPassword" name="password" minlength="8" required>
                </div>
                <div class="col-12 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt me-2"></i> Entrar</button>
                </div>
                <div class="col-12 d-grid">
                    <a href="<?= base_url('register') . ($redirectTarget ? '?redirect=' . urlencode($redirectTarget) : '') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-user-plus me-2"></i> Criar Conta
                    </a>
                </div>
            </form>
        </div>
    </div>

        <div class="collapse" id="forgotCard">
            <div class="card shadow-sm mb-3 border-0">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-lock me-2"></i> Recuperar senha</h6>
                    <form action="<?= base_url('auth/forgot-password') ?>" method="POST" class="mb-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= esc($redirectTarget) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="forgotEmail">E-mail cadastrado</label>
                            <input type="email" class="form-control" id="forgotEmail" name="forgot_email" value="<?= esc(old('forgot_email', session('user_email') ?? '')) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-paper-plane me-2"></i> Enviar código</button>
                    </form>

                    <form action="<?= base_url('auth/reset-password') ?>" method="POST" class="row g-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= esc($redirectTarget) ?>">
                        <div class="col-md-6">
                            <label class="form-label" for="resetEmail">E-mail</label>
                            <input type="email" class="form-control" id="resetEmail" name="reset_email" value="<?= esc(old('reset_email', session('user_email') ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="resetCode">Código recebido</label>
                            <input type="text" class="form-control" id="resetCode" name="user_code" maxlength="6" minlength="6" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="resetNewPassword">Nova senha</label>
                            <input type="password" class="form-control" id="resetNewPassword" name="reset_new_password" minlength="8" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="resetNewPasswordConfirm">Confirmar nova senha</label>
                            <input type="password" class="form-control" id="resetNewPasswordConfirm" name="reset_new_password_confirm" minlength="8" required>
                        </div>
                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-success"><i class="fas fa-check me-2"></i> Validar código e redefinir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="d-grid">
            <a href="<?= base_url('auth/google') . ($redirectTarget ? '?redirect=' . urlencode($redirectTarget) : '') ?>" class="btn btn-danger">
                <i class="fab fa-google me-2"></i> Entrar com Google
            </a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
