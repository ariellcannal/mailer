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
                <?php 
                    // Verificar se pode editar
                    $canEdit = true;
                    if (in_array($message['status'], ['sending', 'sent', 'completed'], true)) {
                        $canEdit = false;
                    }
                    if ($message['status'] === 'scheduled' && $message['scheduled_at']) {
                        $scheduledTime = strtotime($message['scheduled_at']);
                        $now = time();
                        if ($scheduledTime <= $now) {
                            $canEdit = false;
                        }
                    }
                ?>
                <?php if ($canEdit): ?>
                    <a href="<?= base_url('messages/edit/' . $message['id']) ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                <?php endif; ?>
                <form action="<?= base_url('messages/duplicate/' . $message['id']) ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-info">
                        <i class="fas fa-copy"></i> Duplicar
                    </button>
                </form>
                <?php if (in_array($message['status'], ['draft', 'scheduled'], true)): ?>
                    <form action="<?= base_url('messages/delete/' . $message['id']) ?>" method="POST" onsubmit="return confirm('Deseja realmente excluir esta mensagem?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-trash"></i> Excluir
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-3">
            <span class="badge bg-primary text-uppercase">Status: <?= esc($message['status']) ?></span>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="border rounded p-3 mb-3">
                    <h6>Detalhes do Remetente</h6>
                    <p class="mb-1"><strong>Nome:</strong> <?= esc($message['from_name']) ?></p>
                    <p class="mb-1"><strong>Reply-To:</strong> <?= esc($message['reply_to']) ?: 'Não definido' ?></p>
                    <p class="mb-0"><strong>Campanha:</strong> <?= esc($campaignName ?: '-') ?></p>
                </div>
                
                <div class="border rounded p-3">
                    <h6 class="mb-3"><i class="fas fa-clock"></i> Horários de Envio</h6>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-primary me-2">Principal</span>
                            <strong><?= esc($message['subject']) ?></strong>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> 
                            <?= $message['scheduled_at'] ? date('d/m/Y H:i', strtotime($message['scheduled_at'])) : 'Não agendado' ?>
                        </small>
                    </div>
                    
                    <?php if (!empty($resendRules)): ?>
                        <hr class="my-2">
                        <h6 class="small text-muted mb-2">Reenvios</h6>
                        <?php foreach ($resendRules as $rule): ?>
                            <div class="mb-2">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="badge bg-info me-2">Reenvio <?= $rule['resend_number'] ?></span>
                                    <small><strong><?= esc($rule['subject_override'] ?: $message['subject']) ?></strong></small>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i> 
                                    <?= date('d/m/Y H:i', strtotime($rule['scheduled_at'])) ?>
                                    <span class="badge bg-<?= $rule['status'] == 'completed' ? 'success' : 'secondary' ?> ms-1">
                                        <?= $rule['status'] == 'completed' ? 'Concluído' : 'Pendente' ?>
                                    </span>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                        <th>Assunto</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Enviado em</th>
                        <th>Abertura</th>
                        <th>Clique</th>
                        <th>Bounce</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sends)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Nenhum envio registrado para esta mensagem.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sends as $send): ?>
                            <?php 
                                $sendType = 'Principal';
                                if ($send['resend_number'] > 0) {
                                    $sendType = 'Reenvio ' . $send['resend_number'];
                                }
                                $subject = $send['subject_override'] ?? $message['subject'];
                            ?>
                            <tr>
                                <td>#<?= $send['id'] ?></td>
                                <td><?= esc($contactMap[$send['contact_id']] ?? $send['contact_id']) ?></td>
                                <td><small><?= esc($subject) ?></small></td>
                                <td>
                                    <?php if ($send['resend_number'] == 0): ?>
                                        <span class="badge bg-primary">Principal</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Reenvio <?= $send['resend_number'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($send['status']) ?></td>
                                <td><?= $send['sent_at'] ? date('d/m/Y H:i', strtotime($send['sent_at'])) : '-' ?></td>
                                <td><?= $send['opened'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
                                <td><?= $send['clicked'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
                                <td>
                                    <?php if (!empty($send['bounce_type'])): ?>
                                        <?php if ($send['bounce_type'] === 'hard'): ?>
                                            <span class="badge bg-danger" title="Bounce permanente">
                                                <i class="fas fa-times-circle"></i> Hard
                                            </span>
                                        <?php elseif ($send['bounce_type'] === 'soft'): ?>
                                            <span class="badge bg-warning" title="Bounce temporário">
                                                <i class="fas fa-exclamation-triangle"></i> Soft
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-question-circle"></i> Bounce
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('contacts/view/' . $send['contact_id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-user"></i> Ver contato
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
