<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 64px; height: 64px; font-size: 24px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h5 class="mb-0"><?= esc($user['name'] ?? session('user_name')) ?></h5>
                        <small class="text-muted"><?= esc($user['email'] ?? session('user_email')) ?></small>
                    </div>
                </div>

                <div class="mb-2">
                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Conta ativa</span>
                </div>
                <div class="mb-2">
                    <strong>Login atual:</strong>
                    <span class="text-muted ms-1"><?= esc(session('auth_provider') === 'google' ? 'Google OAuth' : 'E-mail e senha') ?></span>
                </div>
                <div class="mb-2">
                    <strong>Último acesso:</strong>
                    <span class="text-muted ms-1"><?= esc($user['last_login'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm mb-4" id="password">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="fas fa-key me-2"></i> Alterar senha</h5>
                <p class="text-muted small">Informe a senha atual para cadastrar uma nova senha com no mínimo 8 caracteres.</p>

                <?php if (session('error')): ?>
                    <div class="alert alert-danger"><?= esc(session('error')) ?></div>
                <?php endif; ?>
                <?php if (session('success')): ?>
                    <div class="alert alert-success"><?= esc(session('success')) ?></div>
                <?php endif; ?>

                <form action="<?= base_url('profile/change-password') ?>" method="POST" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label" for="currentPassword">Senha atual</label>
                        <input type="password" name="current_password" id="currentPassword" class="form-control" required minlength="8">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="newPassword">Nova senha</label>
                        <input type="password" name="new_password" id="newPassword" class="form-control" required minlength="8">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="confirmPassword">Confirmar nova senha</label>
                        <input type="password" name="confirm_new_password" id="confirmPassword" class="form-control" required minlength="8">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Atualizar senha</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1"><i class="fab fa-google me-2"></i> Vincular/Desvincular Google</h6>
                    <p class="text-muted small mb-0">Você pode remover o acesso via Google a qualquer momento.</p>
                </div>
                <form action="<?= base_url('profile/unlink-google') ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-unlink me-2"></i> Desvincular</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
