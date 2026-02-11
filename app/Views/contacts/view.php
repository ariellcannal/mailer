<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-user"></i> <?= esc($contact['name'] ?: $contact['email']) ?></h4>
            <div class="d-flex gap-2">
                <a href="<?= base_url('contacts/edit/' . $contact['id']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <form action="<?= base_url('contacts/delete/' . $contact['id']) ?>" method="POST" onsubmit="return confirm('Deseja remover este contato?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>

        <?php if (session('contacts_success')): ?>
            <div class="alert alert-success"><?= esc(session('contacts_success')) ?></div>
        <?php endif; ?>
        <?php if (session('contacts_error')): ?>
            <div class="alert alert-danger"><?= esc(session('contacts_error')) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6>Informações Principais</h6>
                    <p class="mb-1"><strong>Email:</strong> <?= esc($contact['email']) ?></p>
                    <p class="mb-1"><strong>Nome:</strong> <?= esc($contact['name']) ?: '-' ?></p>
                    <p class="mb-1"><strong>Apelido:</strong> <?= esc($contact['nickname'] ?? '') ?: '-' ?></p>
                    <p class="mb-1"><strong>Status:</strong>
                        <?= (int) $contact['is_active'] === 1 ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?>
                    </p>
                    <p class="mb-0"><strong>Score de Qualidade:</strong> <?= (int) $contact['quality_score'] ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6>Engajamento</h6>
                    <p class="mb-1"><strong>Total de Aberturas:</strong> <?= (int) $contact['total_opens'] ?></p>
                    <p class="mb-1"><strong>Total de Cliques:</strong> <?= (int) $contact['total_clicks'] ?></p>
                    <p class="mb-1"><strong>Última abertura:</strong> <?= $contact['last_open_date'] ? date('d/m/Y H:i', strtotime($contact['last_open_date'])) : '-' ?></p>
                    <p class="mb-0"><strong>Tempo médio de abertura:</strong> <?= $contact['avg_open_time'] ? round($contact['avg_open_time'] / 60, 1) . ' min' : '-' ?></p>
                </div>
            </div>
        </div>

        <div class="border rounded p-3 mt-4">
            <h6>Listas</h6>
            <?php if (!empty($lists)): ?>
                <?php foreach ($lists as $list): ?>
                    <span class="badge bg-secondary me-1 mb-1"><?= esc($list['name']) ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="text-muted">Nenhuma lista associada.</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($sends)): ?>
            <div class="border rounded p-3 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Mensagens enviadas</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Assunto</th>
                                <th>Envio</th>
                                <th>Status</th>
                                <th>Abertura</th>
                                <th>Clique</th>
                                <th>OptOut</th>
                                <th>Bounce</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sends as $send): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('messages/view/' . $send['message_id']) ?>" class="text-decoration-none">
                                            <?= esc($send['subject'] ?? 'Mensagem #' . $send['message_id']) ?>
                                        </a>
                                    </td>
                                    <td><?= $send['sent_at'] ? date('d/m/Y H:i', strtotime($send['sent_at'])) : '-' ?></td>
                                    <td>
                                        <?php if (($send['message_status'] ?? '') === 'sent'): ?>
                                            <span class="badge bg-success">Enviado</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= esc(ucfirst($send['message_status'] ?? 'pendente')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) ($send['opened'] ?? 0) === 1): ?>
                                            <span class="badge bg-success">Sim</span>
                                            <small class="text-muted d-block">Total: <?= (int) ($send['total_opens'] ?? 0) ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) ($send['clicked'] ?? 0) === 1): ?>
                                            <span class="badge bg-success">Sim</span>
                                            <small class="text-muted d-block">Total: <?= (int) ($send['total_clicks'] ?? 0) ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($send['optout_id'])): ?>
                                            <span class="badge bg-danger">Sim</span>
                                            <?php if (!empty($send['opted_out_at'])): ?>
                                                <small class="text-muted d-block"><?= date('d/m/Y H:i', strtotime($send['opted_out_at'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-success">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($send['bounce_id'])): ?>
                                            <?php 
                                                $bounceType = $send['bounce_type'] ?? 'unknown';
                                                $bounceSubtype = $send['bounce_subtype'] ?? '';
                                                $bounceLabel = ucfirst($bounceType);
                                                if ($bounceSubtype) {
                                                    $bounceLabel .= ' (' . ucfirst($bounceSubtype) . ')';
                                                }
                                                $badgeClass = $bounceType === 'hard' ? 'bg-danger' : 'bg-warning';
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= esc($bounceLabel) ?></span>
                                            <?php if (!empty($send['bounced_at'])): ?>
                                                <small class="text-muted d-block"><?= date('d/m/Y H:i', strtotime($send['bounced_at'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-success">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
