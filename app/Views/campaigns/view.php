<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-bullhorn"></i> <?= esc($campaign['name']) ?></h4>
            <div class="d-flex gap-2">
                <a href="<?= base_url('campaigns/edit/' . $campaign['id']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <form action="<?= base_url('campaigns/delete/' . $campaign['id']) ?>" method="POST" onsubmit="return confirm('Deseja excluir esta campanha?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>

        <?php if (!empty($campaign['description'])): ?>
            <p class="text-muted mb-3"><?= esc($campaign['description']) ?></p>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <h6>Total de Mensagens</h6>
                    <h3><?= (int) ($campaign['total_messages'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <h6>Aberturas</h6>
                    <h3><?= (int) ($campaign['total_opens'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <h6>Cliques</h6>
                    <h3><?= (int) ($campaign['total_clicks'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <h6>Opt-outs</h6>
                    <h3><?= (int) ($campaign['total_optouts'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="fas fa-envelope"></i> Mensagens da Campanha</h5>
            <a href="<?= base_url('messages/create') ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Nova Mensagem
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Assunto</th>
                        <th>Status</th>
                        <th>Enviados</th>
                        <th>Aberturas</th>
                        <th>Cliques</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhuma mensagem vinculada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><?= esc($message['subject']) ?></td>
                                <td><?= esc($message['status']) ?></td>
                                <td><?= (int) ($message['total_sent'] ?? 0) ?></td>
                                <td><?= (int) ($message['total_opens'] ?? 0) ?></td>
                                <td><?= (int) ($message['total_clicks'] ?? 0) ?></td>
                                <td>
                                    <a href="<?= base_url('messages/view/' . $message['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
