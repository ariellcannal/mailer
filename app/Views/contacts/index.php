<?= \$this->extend('layouts/main') ?>

<?= \$this->section('content') ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="fas fa-users"></i> Contatos</h4>
            <div>
                <a href="<?= base_url('contacts/import') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-file-upload"></i> Importar
                </a>
                <a href="<?= base_url('contacts/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Novo Contato
                </a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Nome</th>
                        <th>Qualidade</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty(\$contacts)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                Nenhum contato encontrado
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (\$contacts as \$contact): ?>
                            <tr>
                                <td><?= esc(\$contact['email']) ?></td>
                                <td><?= esc(\$contact['name']) ?></td>
                                <td>
                                    <?php for (\$i = 1; \$i <= 5; \$i++): ?>
                                        <i class="fas fa-star <?= \$i <= \$contact['quality_score'] ? 'text-warning' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                </td>
                                <td>
                                    <?php if (\$contact['is_active']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('contacts/view/' . \$contact['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?= \$pager->links() ?>
    </div>
</div>
<?= \$this->endSection() ?>
