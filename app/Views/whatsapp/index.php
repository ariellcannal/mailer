<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="card-body">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="fab fa-whatsapp me-2"></i>Envios WhatsApp</h4>
            <a href="<?= base_url('whatsapp/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus" aria-hidden="true"></i><span>Novo envio</span>
            </a>
        </header>

        <?php if (session('success')): ?><div class="alert alert-success"><?= esc(session('success')) ?></div><?php endif; ?>
        <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Envio</th><th>Campanha</th><th>Template</th><th>Número</th><th>Agendamento</th><th>Status</th><th>Enviados</th><th>Entregues</th><th>Lidos</th></tr></thead>
                <tbody>
                <?php if (empty($messages)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Nenhum envio WhatsApp.</td></tr>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <tr>
                            <td><?= esc($message['name']) ?></td>
                            <td><?= esc($message['campaign_name']) ?></td>
                            <td><?= esc($message['template_name']) ?></td>
                            <td><?= esc($message['display_phone_number']) ?></td>
                            <td><?= esc($message['scheduled_at'] ?? '-') ?></td>
                            <td><span class="badge <?= $message['status'] === 'sent' ? 'bg-success' : ($message['status'] === 'failed' ? 'bg-danger' : 'bg-secondary') ?>"><?= esc(ucfirst($message['status'])) ?></span></td>
                            <td><?= number_format((int) $message['total_sent'], 0, ',', '.') ?></td>
                            <td><?= number_format((int) $message['total_delivered'], 0, ',', '.') ?></td>
                            <td><?= number_format((int) $message['total_read'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?= $this->endSection() ?>