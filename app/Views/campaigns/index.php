<?= \$this->extend('layouts/main') ?>

<?= \$this->section('content') ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="fas fa-bullhorn"></i> Campanhas</h4>
            <a href="<?= base_url('campaigns/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Campanha
            </a>
        </div>
        
        <div class="row">
            <?php if (empty(\$campaigns)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3 d-block"></i>
                    <p class="text-muted">Nenhuma campanha criada ainda</p>
                    <a href="<?= base_url('campaigns/create') ?>" class="btn btn-primary">
                        Criar Primeira Campanha
                    </a>
                </div>
            <?php else: ?>
                <?php foreach (\$campaigns as \$campaign): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= esc(\$campaign['name']) ?></h5>
                                <p class="card-text text-muted"><?= esc(\$campaign['description']) ?></p>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-paper-plane"></i> <?= \$campaign['total_messages'] ?? 0 ?> mensagens<br>
                                        <i class="fas fa-envelope-open"></i> <?= \$campaign['total_opens'] ?? 0 ?> aberturas
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="<?= base_url('campaigns/view/' . \$campaign['id']) ?>" class="btn btn-sm btn-primary">
                                    Ver Detalhes
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= \$this->endSection() ?>
