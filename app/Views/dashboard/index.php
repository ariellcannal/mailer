<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-card-value"><?= number_format($stats['totalContacts'], 0, ',', '.') ?></div>
            <div class="stat-card-label">Total de Contatos</div>
            <small class="text-success">
                <i class="fas fa-check-circle"></i> <?= number_format($stats['activeContacts'], 0, ',', '.') ?> ativos
            </small>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #fff;">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="stat-card-value"><?= number_format($stats['totalSends'], 0, ',', '.') ?></div>
            <div class="stat-card-label">Emails Enviados</div>
            <small class="text-muted">
                <i class="fas fa-envelope"></i> <?= number_format($stats['totalMessages'], 0, ',', '.') ?> mensagens
            </small>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: #fff;">
                <i class="fas fa-envelope-open"></i>
            </div>
            <div class="stat-card-value"><?= $stats['openRate'] ?>%</div>
            <div class="stat-card-label">Taxa de Abertura</div>
            <small class="text-info">
                <i class="fas fa-eye"></i> <?= number_format($stats['totalOpens'], 0, ',', '.') ?> aberturas
            </small>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: #fff;">
                <i class="fas fa-mouse-pointer"></i>
            </div>
            <div class="stat-card-value"><?= $stats['clickRate'] ?>%</div>
            <div class="stat-card-label">Taxa de Cliques</div>
            <small class="text-success">
                <i class="fas fa-hand-pointer"></i> <?= number_format($stats['totalClicks'], 0, ',', '.') ?> cliques
            </small>
        </div>
    </div>
</div>

<!-- AWS SES Limits -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fab fa-aws text-warning"></i> Limites AWS SES
                </h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="mb-1"><?= $sesLimits['maxRate'] ?></h3>
                            <small class="text-muted">Emails/segundo</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="mb-1"><?= $sesLimits['max24Hour'] ?></h3>
                            <small class="text-muted">Limite 24h</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="mb-1"><?= $sesLimits['sentLast24Hours'] ?></h3>
                            <small class="text-muted">Enviados 24h</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="mb-1"><?= $sesLimits['remaining'] ?></h3>
                            <small class="text-muted">Restantes</small>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar <?= $sesLimits['percentUsed'] > 80 ? 'bg-danger' : ($sesLimits['percentUsed'] > 50 ? 'bg-warning' : 'bg-success') ?>" 
                             role="progressbar" 
                             style="width: <?= $sesLimits['percentUsed'] ?>%">
                            <?= $sesLimits['percentUsed'] ?>% utilizado
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line"></i> Performance dos Últimos 7 Dias
                    </h5>
                    <select class="form-select form-select-sm" style="width: auto;" id="chartPeriod" onchange="updateChart()">
                        <option value="7days">7 dias</option>
                        <option value="30days">30 dias</option>
                        <option value="90days">90 dias</option>
                    </select>
                </div>
                <canvas
                    id="performanceChart"
                    height="80"
                    data-chart-url="<?= base_url('dashboard/chart-data') ?>">
                </canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-bullhorn"></i> Campanhas Recentes
                </h5>
                <?php if (empty($recentCampaigns)): ?>
                    <p class="text-muted text-center py-4">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        Nenhuma campanha criada ainda
                    </p>
                    <a href="<?= base_url('campaigns/create') ?>" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Criar Primeira Campanha
                    </a>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentCampaigns as $campaign): ?>
                            <a href="<?= base_url('campaigns/view/' . $campaign['id']) ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= esc($campaign['name']) ?></h6>
                                    <small><?= date('d/m/Y', strtotime($campaign['created_at'])) ?></small>
                                </div>
                                <small class="text-muted">
                                    <?= $campaign['total_messages'] ?> mensagens · 
                                    <?= $campaign['total_opens'] ?> aberturas
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= base_url('campaigns') ?>" class="btn btn-outline-primary w-100 mt-3">
                        Ver Todas
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Messages -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-paper-plane"></i> Mensagens Recentes
                    </h5>
                    <a href="<?= base_url('messages/create') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nova Mensagem
                    </a>
                </div>
                
                <?php if (empty($recentMessages)): ?>
                    <p class="text-muted text-center py-4">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        Nenhuma mensagem criada ainda
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Assunto</th>
                                    <th>Status</th>
                                    <th>Enviados</th>
                                    <th>Aberturas</th>
                                    <th>Cliques</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentMessages as $message): ?>
                                    <tr>
                                        <td><?= esc($message['subject']) ?></td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'draft' => 'secondary',
                                                'scheduled' => 'info',
                                                'sending' => 'warning',
                                                'sent' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $statusLabels = [
                                                'draft' => 'Rascunho',
                                                'scheduled' => 'Agendada',
                                                'sending' => 'Enviando',
                                                'sent' => 'Enviada',
                                                'cancelled' => 'Cancelada'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $statusColors[$message['status']] ?>">
                                                <?= $statusLabels[$message['status']] ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($message['total_sent'], 0, ',', '.') ?></td>
                                        <td>
                                            <?= number_format($message['total_opens'], 0, ',', '.') ?>
                                            <?php if ($message['total_sent'] > 0): ?>
                                                <small class="text-muted">
                                                    (<?= round(($message['total_opens'] / $message['total_sent']) * 100, 1) ?>%)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= number_format($message['total_clicks'], 0, ',', '.') ?>
                                            <?php if ($message['total_sent'] > 0): ?>
                                                <small class="text-muted">
                                                    (<?= round(($message['total_clicks'] / $message['total_sent']) * 100, 1) ?>%)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></td>
                                        <td>
                                            <a href="<?= base_url('messages/view/' . $message['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/dashboard.js') ?>" defer></script>
<?= $this->endSection() ?>
