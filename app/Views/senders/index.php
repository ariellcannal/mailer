<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 section-title"><i class="fas fa-at text-secondary me-2"></i>Remetentes</h4>
            <a href="<?= base_url('senders/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Novo Remetente
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th class="text-uppercase">Email</th>
                        <th class="text-uppercase">Nome</th>
                        <th class="text-uppercase">Domínio</th>
                        <th class="text-uppercase">Status</th>
                        <th class="text-uppercase">Verificações</th>
                        <th class="text-uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($senders)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum remetente cadastrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($senders as $sender): ?>
                            <tr>
                                <td><?= esc($sender['email']) ?></td>
                                <td><?= esc($sender['name']) ?></td>
                                <td><?= esc($sender['domain']) ?></td>
                                <td>
                                    <?php if ((int) $sender['is_active'] === 1): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= (int) $sender['spf_verified'] === 1 ? 'bg-success' : 'bg-warning' ?>">SPF</span>
                                    <span class="badge <?= (int) $sender['dkim_verified'] === 1 ? 'bg-success' : 'bg-warning' ?>">DKIM</span>
                                    <span class="badge <?= (int) $sender['dmarc_verified'] === 1 ? 'bg-success' : 'bg-warning' ?>">DMARC</span>
                                </td>
                                <td>
                                    <a href="<?= base_url('senders/view/' . $sender['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('senders/edit/' . $sender['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="<?= base_url('senders/delete/' . $sender['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Deseja remover este remetente?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
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
