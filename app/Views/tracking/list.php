<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-chart-line"></i> <?= esc($pageTitle) ?></h4>
            <a href="<?= base_url('tracking') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <?php if ($type === 'opens'): ?>
                            <th>ID</th>
                            <th>Envio</th>
                            <th>Aberto em</th>
                            <th>IP</th>
                        <?php elseif ($type === 'clicks'): ?>
                            <th>ID</th>
                            <th>Envio</th>
                            <th>URL</th>
                            <th>Data</th>
                        <?php elseif ($type === 'bounces'): ?>
                            <th>ID</th>
                            <th>Contato</th>
                            <th>Status</th>
                            <th>Data</th>
                        <?php else: ?>
                            <th>ID</th>
                            <th>Contato</th>
                            <th>Mensagem</th>
                            <th>Data</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Nenhum registro encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <?php if ($type === 'opens'): ?>
                                    <td>#<?= $record['id'] ?></td>
                                    <td><?= $record['send_id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($record['opened_at'])) ?></td>
                                    <td><?= esc($record['ip_address']) ?></td>
                                <?php elseif ($type === 'clicks'): ?>
                                    <td>#<?= $record['id'] ?></td>
                                    <td><?= $record['send_id'] ?></td>
                                    <td><a href="<?= esc($record['link_url']) ?>" target="_blank"><?= esc($record['link_url']) ?></a></td>
                                    <td><?= date('d/m/Y H:i', strtotime($record['clicked_at'])) ?></td>
                                <?php elseif ($type === 'bounces'): ?>
                                    <td>#<?= $record['id'] ?></td>
                                    <td><?= esc($record['contact_id']) ?></td>
                                    <td><?= esc($record['status']) ?></td>
                                    <td><?= $record['bounced_at'] ? date('d/m/Y H:i', strtotime($record['bounced_at'])) : '-' ?></td>
                                <?php else: ?>
                                    <td>#<?= $record['id'] ?></td>
                                    <td><?= esc($record['contact_id']) ?></td>
                                    <td><?= $record['message_id'] ? esc($record['message_id']) : '-' ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($record['opted_out_at'])) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
