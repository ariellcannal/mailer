<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="fas fa-paper-plane"></i> Mensagens</h4>
            <a href="<?= base_url('messages/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Mensagem
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Assunto</th>
                        <th>Campanha</th>
                        <th>Remetente</th>
                        <th>Status</th>
                        <th>Criada em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                Nenhuma mensagem cadastrada
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><?= esc($message['subject']) ?></td>
                                <td><?= esc($campaignMap[$message['campaign_id']] ?? '-') ?></td>
                                <td><?= esc($senderMap[$message['sender_id']] ?? $message['from_name']) ?></td>
                                <td>
                                    <?php if ($message['status'] === 'sent'): ?>
                                        <span class="badge bg-success">Enviada</span>
                                    <?php elseif ($message['status'] === 'sending'): ?>
                                        <span class="badge bg-info">Enviando</span>
                                    <?php elseif ($message['status'] === 'scheduled'): ?>
                                        <span class="badge bg-warning">Agendada</span>
                                    <?php elseif ($message['status'] === 'cancelled'): ?>
                                        <span class="badge bg-secondary">Cancelada</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Rascunho</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></td>
                                <td>
                                    <a href="<?= base_url('messages/view/' . $message['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('messages/edit/' . $message['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="<?= base_url('messages/duplicate/' . $message['id']) ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?= $pager->links() ?>
    </div>
</div>
<?= $this->endSection() ?>
