<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h4 class="text-center mb-3"><i class="fas fa-user-circle"></i> Acessar painel</h4>

                <?php if (session('error')): ?>
                    <div class="alert alert-danger"><?= esc(session('error')) ?></div>
                <?php endif; ?>
                <?php if (session('success')): ?>
                    <div class="alert alert-success"><?= esc(session('success')) ?></div>
                <?php endif; ?>

                <p class="text-muted small">Apenas o e-mail autorizado (<?= esc($allowedEmail) ?>) pode se registrar ou acessar.</p>

                <form action="<?= base_url('login') ?>" method="POST" class="mb-3">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="loginEmail">E-mail</label>
                        <input type="email" class="form-control" id="loginEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="loginPassword">Senha</label>
                        <input type="password" class="form-control" id="loginPassword" name="password" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </button>
                </form>

                <div class="border-top pt-3">
                    <h6 class="text-center">Novo por aqui?</h6>
                    <form action="<?= base_url('register') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-2">
                            <label class="form-label" for="registerName">Nome</label>
                            <input type="text" class="form-control" id="registerName" name="name" placeholder="Seu nome completo" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="registerEmail">E-mail autorizado</label>
                            <input type="email" class="form-control" id="registerEmail" name="email" value="<?= esc($allowedEmail) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="registerPassword">Senha (mínimo 8 caracteres)</label>
                            <input type="password" class="form-control" id="registerPassword" name="password" minlength="8" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-user-plus"></i> Criar conta
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-center mb-3">Preferir continuar com Google?</h6>
                <p class="text-muted small text-center">Se o e-mail autorizado já existir, o Google será vinculado e o acesso por senha continuará disponível.</p>
                <a href="<?= base_url('auth/google') ?>" class="btn btn-danger w-100">
                    <i class="fab fa-google"></i> Entrar com Google
                </a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
