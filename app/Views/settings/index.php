<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card mb-4">
    <div class="card-body">
        <h4 class="mb-4 section-title"><i class="fas fa-cog text-secondary me-2"></i> Configurações Gerais</h4>

        <?php if (session('success')): ?>
            <div class="alert alert-success"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <div class="alert alert-info d-flex align-items-center gap-3">
            <img src="<?= base_url('assets/images/icon.svg') ?>" alt="Ícone da CANNAL" width="48" height="48">
            <div>
                <strong>Credenciais AWS SES</strong><br>
                Os dados de autenticação são carregados exclusivamente do arquivo <code>.env</code>.<br>
                Região configurada: <span class="badge bg-primary"><?= esc($awsConfig['region']) ?></span>
                <?php if ($awsConfig['hasAccessKey'] && $awsConfig['hasSecretKey']): ?>
                    <span class="badge bg-success ms-2">Chaves configuradas</span>
                <?php else: ?>
                    <span class="badge bg-danger ms-2">Chaves ausentes</span>
                <?php endif; ?>
            </div>
        </div>

        <form action="<?= base_url('settings/update') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Taxa de envio (emails/seg)</label>
                    <input type="number" class="form-control" name="settings[throttle_rate]" value="<?= esc($settings['throttle_rate'] ?? '14') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Remetente padrão</label>
                    <input type="email" class="form-control" name="settings[default_sender]" value="<?= esc($settings['default_sender'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Editor de conteúdo</label>
                    <select class="form-select" name="settings[editor_engine]">
                        <?php $currentEditor = $settings['editor_engine'] ?? 'tinymce'; ?>
                        <option value="tinymce" <?= $currentEditor === 'tinymce' ? 'selected' : '' ?>>TinyMCE</option>
                        <option value="ckeditor" <?= $currentEditor === 'ckeditor' ? 'selected' : '' ?>>CKEditor</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnSesLimits">
                    <i class="fas fa-sync"></i> Consultar limites SES
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3"><i class="fab fa-aws"></i> Limites atuais</h5>
        <div id="sesLimits" class="text-muted">Clique no botão acima para carregar os limites.</div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function() {
    $('#btnSesLimits').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Consultando...');

        $.getJSON('<?= base_url('settings/ses-limits') ?>')
            .done(function(response) {
                if (response.success === false) {
                    $('#sesLimits').html('<div class="alert alert-danger">' + response.message + '</div>');
                    return;
                }

                $('#sesLimits').html(`
                    <div class="row">
                        <div class="col-md-3"><strong>Limite 24h:</strong> ${response.max24HourSend}</div>
                        <div class="col-md-3"><strong>Taxa Máxima:</strong> ${response.maxSendRate}</div>
                        <div class="col-md-3"><strong>Enviados 24h:</strong> ${response.sentLast24Hours}</div>
                        <div class="col-md-3"><strong>Restante:</strong> ${response.remaining}</div>
                    </div>
                `);
            })
            .fail(function() {
                $('#sesLimits').html('<div class="alert alert-danger">Erro ao consultar limites.</div>');
            })
            .always(function() {
                button.prop('disabled', false).html('<i class="fas fa-sync"></i> Consultar limites SES');
            });
    });
});
</script>
<?= $this->endSection() ?>
