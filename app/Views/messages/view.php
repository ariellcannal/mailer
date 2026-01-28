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
                    // Verificar se pode mostrar botão editar
                    $showEditButton = true;
                    $now = time();
                    
                    // Não mostrar para mensagens em envio ou completadas
                    if (in_array($message['status'], ['sending', 'sent', 'completed'], true)) {
                        $showEditButton = false;
                    }
                    
                    // Se mensagem agendada, não mostrar botão (mostrará prompt ao clicar)
                    if ($message['status'] === 'scheduled') {
                        $showEditButton = false;
                    }
                    
                    // Se primeiro envio já passou, verificar se há reenvios futuros
                    if (!empty($message['scheduled_at'])) {
                        $scheduledTime = strtotime($message['scheduled_at']);
                        if ($scheduledTime < $now) {
                            // Primeiro envio passou, verificar reenvios
                            $hasFutureResends = false;
                            if (!empty($resendRules)) {
                                foreach ($resendRules as $rule) {
                                    if (!empty($rule['scheduled_at'])) {
                                        $resendTime = strtotime($rule['scheduled_at']);
                                        if ($resendTime >= $now) {
                                            $hasFutureResends = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            // Se há reenvios futuros, pode editar (apenas reenvios)
                            if ($hasFutureResends) {
                                $showEditButton = true;
                            } else {
                                // Todos envios passaram, não mostrar botão
                                $showEditButton = false;
                            }
                        }
                    }
                ?>
                <?php if ($showEditButton): ?>
                    <?php if ($message['status'] === 'scheduled'): ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="handleScheduledEdit(<?= $message['id'] ?>, '<?= $message['scheduled_at'] ?>')">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    <?php else: ?>
                        <a href="<?= base_url('messages/edit/' . $message['id']) ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="<?= base_url('webview/' . $message['tracking_token']) ?>" target="_blank" class="btn btn-outline-success">
                    <i class="fas fa-eye"></i> Pré-visualizar
                </a>
                <form action="<?= base_url('messages/duplicate/' . $message['id']) ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-info">
                        <i class="fas fa-copy"></i> Duplicar
                    </button>
                </form>
                <?php if ($message['status'] === 'draft'): ?>
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

        <!-- Detalhes do Remetente -->
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <div class="border rounded p-3">
                    <h6>Detalhes do Remetente</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <p class="mb-1"><strong>Remetente:</strong> <?= esc($senderEmail ?: '-') ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><strong>Nome:</strong> <?= esc($message['from_name']) ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><strong>Reply-To:</strong> <?= esc($message['reply_to']) ?: 'Não definido' ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-0"><strong>Campanha:</strong> <?= esc($campaignName ?: '-') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Horários de Envio (Largura Total) -->
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <div class="border rounded p-3">
                    <h6 class="mb-3"><i class="fas fa-clock"></i> Horários de Envio</h6>
                    
                    <?php
                    // Montar array com todos os envios (principal + reenvios)
                    $allSchedules = [];
                    
                    // Adicionar envio principal
                    if ($message['scheduled_at']) {
                        $allSchedules[] = [
                            'type' => 'principal',
                            'number' => 0,
                            'subject' => $message['subject'],
                            'scheduled_at' => $message['scheduled_at'],
                            'status' => $message['status'],
                            'timestamp' => strtotime($message['scheduled_at'])
                        ];
                    }
                    
                    // Adicionar reenvios
                    if (!empty($resendRules)) {
                        foreach ($resendRules as $rule) {
                            if (!empty($rule['scheduled_at'])) {
                                $allSchedules[] = [
                                    'type' => 'reenvio',
                                    'number' => $rule['resend_number'],
                                    'subject' => $rule['subject_override'] ?: $message['subject'],
                                    'scheduled_at' => $rule['scheduled_at'],
                                    'status' => $rule['status'],
                                    'timestamp' => strtotime($rule['scheduled_at'])
                                ];
                            }
                        }
                    }
                    
                    // Ordenar por data cronológica
                    usort($allSchedules, function($a, $b) {
                        return $a['timestamp'] - $b['timestamp'];
                    });
                    ?>
                    
                    <?php if (empty($allSchedules)): ?>
                        <p class="text-muted mb-0">Nenhum horário de envio definido.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="15%">Tipo</th>
                                        <th width="45%">Assunto</th>
                                        <th width="20%">Data/Hora</th>
                                        <th width="20%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allSchedules as $schedule): ?>
                                        <tr>
                                            <td>
                                                <?php if ($schedule['type'] === 'principal'): ?>
                                                    <span class="badge bg-primary">Principal</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Reenvio <?= $schedule['number'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= esc($schedule['subject']) ?></strong>
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar"></i> 
                                                <?= date('d/m/Y H:i', $schedule['timestamp']) ?>
                                            </td>
                                            <td>
                                                <?php
                                                $now = time();
                                                $isPast = $schedule['timestamp'] < $now;
                                                
                                                if ($schedule['type'] === 'principal') {
                                                    if ($schedule['status'] === 'sent' || $schedule['status'] === 'completed') {
                                                        echo '<span class="badge bg-success"><i class="fas fa-check"></i> Processado</span>';
                                                    } elseif ($schedule['status'] === 'sending') {
                                                        echo '<span class="badge bg-warning"><i class="fas fa-spinner"></i> Enviando</span>';
                                                    } elseif ($isPast) {
                                                        echo '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Atrasado</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary"><i class="fas fa-clock"></i> Pendente</span>';
                                                    }
                                                } else {
                                                    if ($schedule['status'] === 'completed') {
                                                        echo '<span class="badge bg-success"><i class="fas fa-check"></i> Processado</span>';
                                                    } elseif ($schedule['status'] === 'skipped') {
                                                        echo '<span class="badge bg-warning"><i class="fas fa-forward"></i> Ignorado</span>';
                                                    } elseif ($schedule['status'] === 'cancelled') {
                                                        echo '<span class="badge bg-danger"><i class="fas fa-times"></i> Cancelado</span>';
                                                    } elseif ($isPast) {
                                                        echo '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Atrasado</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary"><i class="fas fa-clock"></i> Pendente</span>';
                                                    }
                                                }
                                                ?>
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

<?= $this->section('scripts') ?>
<script>
function handleScheduledEdit(messageId, scheduledAt) {
    // Calcular tempo até o envio
    const scheduledTime = new Date(scheduledAt.replace(' ', 'T')).getTime();
    const now = new Date().getTime();
    const timeUntilSend = Math.floor((scheduledTime - now) / 1000); // segundos
    
    // Se falta menos de 1 minuto (60 segundos)
    if (timeUntilSend > 0 && timeUntilSend < 60) {
        const confirmMsg = `Esta mensagem está agendada para envio em menos de 1 minuto (${timeUntilSend}s). Deseja transformá-la em rascunho para poder editá-la?`;
        
        if (typeof alertify !== 'undefined') {
            alertify.confirm(
                'Transformar em Rascunho?',
                confirmMsg,
                function() {
                    // Confirmou: transformar em rascunho
                    $.post('/messages/convert-to-draft/' + messageId, function(response) {
                        alertify.success('Mensagem transformada em rascunho');
                        window.location.href = '/messages/edit/' + messageId;
                    }).fail(function() {
                        alertify.error('Erro ao transformar mensagem em rascunho');
                    });
                },
                function() {
                    // Cancelou
                    alertify.message('Operação cancelada');
                }
            );
        } else {
            if (confirm(confirmMsg)) {
                $.post('/messages/convert-to-draft/' + messageId, function(response) {
                    alert('Mensagem transformada em rascunho');
                    window.location.href = '/messages/edit/' + messageId;
                }).fail(function() {
                    alert('Erro ao transformar mensagem em rascunho');
                });
            }
        }
    } else {
        // Mais de 1 minuto ou já passou: não permitir edição
        const msg = 'Mensagens agendadas não podem ser editadas. Para editar, cancele o agendamento primeiro.';
        if (typeof alertify !== 'undefined') {
            alertify.error(msg);
        } else {
            alert(msg);
        }
    }
}
</script>
<?= $this->endSection() ?>
