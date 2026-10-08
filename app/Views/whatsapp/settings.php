<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="card mb-4">
    <div class="card-body">
        <h4 class="mb-3">Conexão WhatsApp Business</h4>
        <?php if (session('success')): ?><div class="alert alert-success"><?= esc(session('success')) ?></div><?php endif; ?>
        <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif; ?>
        <div id="metaEmbeddedSignup"
             data-app-id="<?= esc($metaAppId) ?>"
             data-config-id="<?= esc($metaConfigId) ?>"
             data-graph-version="<?= esc($graphVersion) ?>"
             data-connect-url="<?= base_url('settings/whatsapp/connect') ?>">
            <input type="hidden" data-csrf-name value="<?= esc(csrf_token()) ?>">
            <input type="hidden" data-csrf-hash value="<?= esc(csrf_hash()) ?>">
            <?php if (!$hasAccessToken): ?>
                <p class="text-secondary mb-3">Conecte a conta WhatsApp Business da empresa usando a autenticação segura da Meta.</p>
            <?php else: ?>
                <p class="text-success mb-3"><i class="fas fa-check-circle me-2"></i>Conta Meta conectada<?= $wabaId !== '' ? ' · WABA ' . esc($wabaId) : '' ?></p>
            <?php endif; ?>
            <?php if ($metaAppId === '' || $metaConfigId === ''): ?>
                <div class="alert alert-warning mb-0">Configure `whatsapp.appId` e `whatsapp.configId` no ambiente para habilitar a autenticação Meta.</div>
            <?php else: ?>
                <button type="button" class="btn btn-primary" id="connectWhatsAppMeta">
                    <i class="fab fa-meta me-2" aria-hidden="true"></i><?= $hasAccessToken ? 'Reconectar com a Meta' : 'Conectar com a Meta' ?>
                </button>
                <div id="whatsappConnectFeedback" class="mt-3" role="status" aria-live="polite"></div>
            <?php endif; ?>
            <div class="mt-3">
                <label class="form-label" for="waWebhookUrl">URL de callback da Meta</label>
                <input id="waWebhookUrl" class="form-control" value="<?= esc(base_url('webhooks/whatsapp')) ?>" readonly>
                <small class="text-muted">Configure `whatsapp.webhookVerifyToken` e `whatsapp.appSecret` no ambiente e assine o campo `messages` no Webhooks da Meta.</small>
            </div>
        </div>
        <?php if ($hasAccessToken): ?>
            <form action="<?= base_url('settings/whatsapp/sync-numbers') ?>" method="POST" class="mt-3">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-sync me-2" aria-hidden="true"></i>Atualizar números e qualidade</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="card">
    <div class="card-body">
        <h5 class="mb-3">Números conectados</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Nome verificado</th><th>Número</th><th>Status</th><th>Qualidade</th></tr></thead>
                <tbody>
                <?php if (empty($numbers)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Nenhum número sincronizado.</td></tr>
                <?php else: ?>
                    <?php foreach ($numbers as $number): ?>
                        <tr>
                            <td><?= esc($number['verified_name'] ?: '-') ?></td>
                            <td><?= esc($number['display_phone_number']) ?></td>
                            <td><?= esc($number['status']) ?></td>
                            <td><span class="badge <?= strtoupper((string) $number['quality_rating']) === 'GREEN' ? 'bg-success' : (strtoupper((string) $number['quality_rating']) === 'RED' ? 'bg-danger' : 'bg-secondary') ?>"><?= esc($number['quality_rating'] ?: 'Sem dados') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/whatsapp-embedded-signup.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/whatsapp-embedded-signup.js') ?>" defer></script>
<?= $this->endSection() ?>