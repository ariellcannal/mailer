<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-card-icon" style="background: #3498db; color: #fff;">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="stat-card-value"><?= number_format($metrics['sent'], 0, ',', '.') ?></div>
            <div class="stat-card-label">Emails Enviados</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-card-icon" style="background: #1abc9c; color: #fff;">
                <i class="fas fa-envelope-open"></i>
            </div>
            <div class="stat-card-value"><?= number_format($metrics['opens'], 0, ',', '.') ?></div>
            <div class="stat-card-label">Aberturas</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-card-icon" style="background: #e67e22; color: #fff;">
                <i class="fas fa-mouse-pointer"></i>
            </div>
            <div class="stat-card-value"><?= number_format($metrics['clicks'], 0, ',', '.') ?></div>
            <div class="stat-card-label">Cliques</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-card-icon" style="background: #e74c3c; color: #fff;">
                <i class="fas fa-ban"></i>
            </div>
            <div class="stat-card-value"><?= number_format($metrics['optouts'], 0, ',', '.') ?></div>
            <div class="stat-card-label">Descadastros</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-3"><i class="fas fa-stream"></i> Últimas atividades</h5>
        <?php if (empty($recentActivity)): ?>
            <p class="text-muted">Nenhuma atividade registrada nos últimos eventos.</p>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentActivity as $event): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <?php if ($event['type'] === 'open'): ?>
                                <i class="fas fa-envelope-open text-success me-2"></i>Abertura de email
                            <?php elseif ($event['type'] === 'click'): ?>
                                <i class="fas fa-mouse-pointer text-primary me-2"></i> Clique registrado
                            <?php else: ?>
                                <i class="fas fa-user-slash text-danger me-2"></i> Descadastro
                            <?php endif; ?>
                        </span>
                        <span class="text-muted"><?= esc($event['time']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3"><i class="fas fa-filter"></i> Ações rápidas</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('tracking/opens') ?>" class="btn btn-outline-primary">
                <i class="fas fa-envelope-open"></i> Ver aberturas
            </a>
            <a href="<?= base_url('tracking/clicks') ?>" class="btn btn-outline-primary">
                <i class="fas fa-mouse-pointer"></i> Ver cliques
            </a>
            <a href="<?= base_url('tracking/bounces') ?>" class="btn btn-outline-primary">
                <i class="fas fa-ban"></i> Ver bounces
            </a>
            <a href="<?= base_url('tracking/optouts') ?>" class="btn btn-outline-primary">
                <i class="fas fa-user-slash"></i> Ver descadastros
            </a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
