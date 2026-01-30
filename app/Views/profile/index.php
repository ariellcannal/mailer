<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .avatar-preview {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        overflow: hidden;
        background: #f8f9fa;
    }

    .avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $displayName = $user['name'] ?? session('user_name');
    $displayEmail = $user['email'] ?? session('user_email');
    $avatarPath = $user['avatar'] ?? session('user_avatar');
    $avatarUrl = ($avatarPath && str_starts_with((string) $avatarPath, 'http'))
        ? $avatarPath
        : base_url($avatarPath ?: 'assets/images/icon_neg.png');
    $pendingEmail = $user['pending_email'] ?? null;
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <div class="avatar-preview mx-auto mb-3 border">
                    <img src="<?= esc($avatarUrl) ?>" alt="Avatar atual">
                </div>
                <h5 class="mb-0"><?= esc($displayName) ?></h5>
                <small class="text-muted d-block mb-3"><?= esc($displayEmail) ?></small>

                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Conta ativa</span>

                <div class="mt-3">
                    <div class="small text-muted">Login atual: <?= esc(session('auth_provider') === 'google' ? 'Google OAuth' : 'E-mail e senha') ?></div>
                    <div class="small text-muted">Último acesso: <?= esc($user['last_login'] ?? 'N/A') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="fas fa-id-card me-2"></i> Dados de perfil</h5>
                <form action="<?= base_url('profile/update') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="profileName">Nome</label>
                            <input type="text" name="name" id="profileName" class="form-control" value="<?= esc($displayName) ?>" required minlength="3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block" for="avatarInput">Avatar</label>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-preview border">
                                    <img id="avatarPreview" src="<?= esc($avatarUrl) ?>" alt="Pré-visualização do avatar">
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control" id="avatarInput" accept="image/*">
                                    <input type="hidden" name="avatar_cropped" id="avatarCropped">
                                    <small class="text-muted d-block">Ajuste o recorte 1:1 antes de salvar.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Salvar perfil</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4" id="email">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="fas fa-envelope me-2"></i> Alterar e-mail</h5>
                <?php if ($pendingEmail): ?>
                    <div class="alert alert-warning mb-3">Código enviado para <strong><?= esc($pendingEmail) ?></strong>. Informe o código abaixo para concluir.</div>
                <?php endif; ?>
                <form action="<?= base_url('profile/request-email-code') ?>" method="POST" class="row g-3 mb-3">
                    <?= csrf_field() ?>
                    <div class="col-md-8">
                        <label class="form-label" for="newEmail">Novo e-mail</label>
                        <input type="email" class="form-control" id="newEmail" name="new_email" value="<?= esc(old('new_email', $pendingEmail ?? '')) ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-paper-plane me-2"></i> Enviar código</button>
                    </div>
                </form>
                <form action="<?= base_url('profile/confirm-email') ?>" method="POST" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-md-8">
                        <label class="form-label" for="emailCode">Código recebido</label>
                        <input type="text" class="form-control" id="emailCode" name="email_code" maxlength="6" minlength="6" <?= $pendingEmail ? '' : 'disabled' ?> required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100" <?= $pendingEmail ? '' : 'disabled' ?>><i class="fas fa-check me-2"></i> Confirmar e-mail</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4" id="password">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="fas fa-key me-2"></i> Alterar senha</h5>
                <p class="text-muted small">Informe a senha atual para cadastrar uma nova senha com no mínimo 8 caracteres.</p>

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
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h6 class="mb-1"><i class="fab fa-google me-2"></i> Vincular/Desvincular Google</h6>
                    <p class="text-muted small mb-0">Você pode conectar ou remover o acesso via Google a qualquer momento.</p>
                </div>
                <?php if (!empty($user['google_id'])): ?>
                    <form action="<?= base_url('profile/unlink-google') ?>" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger"><i class="fas fa-unlink me-2"></i> Desvincular</button>
                    </form>
                <?php else: ?>
                    <form action="<?= base_url('profile/link-google') ?>" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-success"><i class="fab fa-google me-2"></i> Vincular com o Google</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="avatarCropperModal" tabindex="-1" aria-labelledby="avatarCropperLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avatarCropperLabel">Ajustar recorte do avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-1x1 bg-light">
                    <img id="avatarCropperImage" class="d-none" alt="Recorte do avatar">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="applyAvatarCrop"><i class="fas fa-crop me-2"></i> Aplicar recorte</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (() => {
        const avatarInput = document.getElementById('avatarInput');
        const avatarPreview = document.getElementById('avatarPreview');
        const avatarCropped = document.getElementById('avatarCropped');
        const cropperModalEl = document.getElementById('avatarCropperModal');
        const cropperImage = document.getElementById('avatarCropperImage');
        const applyCropButton = document.getElementById('applyAvatarCrop');
        let cropperInstance = null;

        if (!avatarInput || !cropperModalEl) {
            return;
        }

        const cropperModal = new bootstrap.Modal(cropperModalEl);

        avatarInput.addEventListener('change', (event) => {
            const [file] = event.target.files || [];

            if (!file) {
                return;
            }

            const url = URL.createObjectURL(file);
            cropperImage.src = url;
            cropperImage.classList.remove('d-none');
            cropperModal.show();
        });

        cropperModalEl.addEventListener('shown.bs.modal', () => {
            if (cropperInstance) {
                cropperInstance.destroy();
            }

            cropperInstance = new Cropper(cropperImage, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
                guides: false,
                movable: false,
                zoomable: true,
            });
        });

        cropperModalEl.addEventListener('hidden.bs.modal', () => {
            if (cropperInstance) {
                cropperInstance.destroy();
                cropperInstance = null;
            }
        });

        applyCropButton.addEventListener('click', () => {
            if (!cropperInstance) {
                return;
            }

            const canvas = cropperInstance.getCroppedCanvas({ width: 512, height: 512 });

            if (!canvas) {
                return;
            }

            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            avatarCropped.value = dataUrl;
            avatarPreview.src = dataUrl;
            cropperModal.hide();
        });
    })();
</script>
<?= $this->endSection() ?>
