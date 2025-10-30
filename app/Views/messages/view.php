<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1"><i class="fas fa-paper-plane"></i> <?= esc($message['subject']) ?></h4>
                <small class="text-muted">Criada em <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></small>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('messages/edit/' . $message['id']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <form action="<?= base_url('messages/duplicate/' . $message['id']) ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-info">
                        <i class="fas fa-copy"></i> Duplicar
                    </button>
                </form>
            </div>
        </div>

        <div class="mb-3">
            <span class="badge bg-primary text-uppercase">Status: <?= esc($message['status']) ?></span>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <h6>Detalhes do Remetente</h6>
                    <p class="mb-1"><strong>Nome:</strong> <?= esc($message['from_name']) ?></p>
                    <p class="mb-1"><strong>Reply-To:</strong> <?= esc($message['reply_to']) ?: 'Não definido' ?></p>
                    <p class="mb-0"><strong>Campanha:</strong> <?= esc($message['campaign_id']) ?: '-' ?></p>
                </div>
            </div>
            <div class="col-md-8">
                <div class="border rounded p-3 bg-light">
                    <h6 class="mb-3">Pré-visualização</h6>
                    <div class="bg-white p-3 rounded shadow-sm" style="max-height: 400px; overflow:auto;">
                        <?= $message['html_content'] ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3"><i class="fas fa-users"></i> Últimos envios</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Contato</th>
                        <th>Status</th>
                        <th>Enviado em</th>
                        <th>Abertura</th>
                        <th>Clique</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sends)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Nenhum envio registrado para esta mensagem.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sends as $send): ?>
                            <tr>
                                <td>#<?= $send['id'] ?></td>
                                <td><?= esc($contactMap[$send['contact_id']] ?? $send['contact_id']) ?></td>
                                <td><?= esc($send['status']) ?></td>
                                <td><?= $send['sent_at'] ? date('d/m/Y H:i', strtotime($send['sent_at'])) : '-' ?></td>
                                <td><?= $send['opened'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
                                <td><?= $send['clicked'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
