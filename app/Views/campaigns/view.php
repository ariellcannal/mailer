<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="card campaign-detail">
    <div class="card-body">
        <header class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="campaign-title mb-1"><i class="fas fa-bullhorn me-2"></i><?= esc($campaign['name']) ?></h4>
                <?php if (!empty($campaign['description'])): ?><p class="text-muted mb-0"><?= esc($campaign['description']) ?></p><?php endif; ?>
            </div>
            <div class="btn-group" role="group" aria-label="Ações da campanha">
                <a href="<?= base_url('campaigns/edit/' . $campaign['id']) ?>" class="btn btn-outline-secondary" title="Editar campanha" aria-label="Editar campanha"><i class="fas fa-edit" aria-hidden="true"></i></a>
                <form action="<?= base_url('campaigns/delete/' . $campaign['id']) ?>" method="POST" onsubmit="return confirm('Deseja excluir esta campanha?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger" title="Excluir campanha" aria-label="Excluir campanha"><i class="fas fa-trash" aria-hidden="true"></i></button>
                </form>
            </div>
        </header>

        <ul class="nav nav-tabs campaign-tabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="campaign-sends-tab" data-bs-toggle="tab" data-bs-target="#campaign-sends" type="button" role="tab" aria-controls="campaign-sends" aria-selected="true">Envios</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="campaign-tracking-tab" data-bs-toggle="tab" data-bs-target="#campaign-tracking" type="button" role="tab" aria-controls="campaign-tracking" aria-selected="false">Rastreamento</button></li>
        </ul>

        <div class="tab-content pt-4">
            <div class="tab-pane fade show active" id="campaign-sends" role="tabpanel" aria-labelledby="campaign-sends-tab">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <h5 class="mb-0">Canais da campanha</h5>
                    <div class="btn-group" role="group" aria-label="Criar envio para campanha">
                        <a href="<?= base_url('messages/create?campaign_id=' . $campaign['id']) ?>" class="btn btn-primary"><i class="fas fa-envelope" aria-hidden="true"></i><span>Novo envio de E-mail</span></a>
                        <a href="<?= base_url('whatsapp/create?campaign_id=' . $campaign['id']) ?>" class="btn btn-accent"><i class="fab fa-whatsapp" aria-hidden="true"></i><span>Novo envio WhatsApp</span></a>
                    </div>
                </div>

                <h6 class="section-subtitle mb-2">E-mail</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Assunto</th><th>Status</th><th>Enviados</th><th>Aberturas</th><th>Cliques</th><th>Ações</th></tr></thead>
                        <tbody>
                        <?php if (empty($messages)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Nenhum envio de e-mail nesta campanha.</td></tr>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td><?= esc($message['subject']) ?></td>
                                    <td><span class="badge bg-secondary"><?= esc(ucfirst($message['status'])) ?></span></td>
                                    <td><?= (int) ($message['total_sent'] ?? 0) ?></td>
                                    <td><?= (int) ($message['total_opens'] ?? 0) ?></td>
                                    <td><?= (int) ($message['total_clicks'] ?? 0) ?></td>
                                    <td><a href="<?= base_url('messages/view/' . $message['id']) ?>" class="btn btn-sm btn-outline-primary" title="Ver envio" aria-label="Ver envio"><i class="fas fa-eye" aria-hidden="true"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h6 class="section-subtitle mb-2">WhatsApp</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Envio</th><th>Template</th><th>Número</th><th>Status</th><th>Enviados</th><th>Entregues</th><th>Lidos</th></tr></thead>
                        <tbody>
                        <?php if (empty($whatsappMessages)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">Nenhum envio WhatsApp nesta campanha.</td></tr>
                        <?php else: ?>
                            <?php foreach ($whatsappMessages as $whatsappMessage): ?>
                                <tr>
                                    <td><?= esc($whatsappMessage['name']) ?></td>
                                    <td><?= esc($whatsappMessage['template_name']) ?></td>
                                    <td><?= esc($whatsappMessage['display_phone_number']) ?></td>
                                    <td><span class="badge bg-secondary"><?= esc(ucfirst($whatsappMessage['status'])) ?></span></td>
                                    <td><?= (int) $whatsappMessage['total_sent'] ?></td>
                                    <td><?= (int) $whatsappMessage['total_delivered'] ?></td>
                                    <td><?= (int) $whatsappMessage['total_read'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="campaign-tracking" role="tabpanel" aria-labelledby="campaign-tracking-tab">
                <div class="row g-3">
                    <div class="col-12"><h5 class="mb-1">E-mail</h5></div>
                    <?php foreach (['Enviados' => 'sent', 'Aberturas' => 'opened', 'Cliques' => 'clicked', 'Bounces' => 'bounced', 'Opt-outs' => 'optouts'] as $label => $key): ?>
                        <div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-card-value"><?= (int) ($emailStats[$key] ?? 0) ?></div><div class="stat-card-label"><?= esc($label) ?></div></div></div>
                    <?php endforeach; ?>
                    <div class="col-12 mt-4"><h5 class="mb-1">WhatsApp</h5></div>
                    <?php foreach (['Enviados' => 'sent', 'Entregues' => 'delivered', 'Lidos' => 'read', 'Falhas' => 'failed'] as $label => $key): ?>
                        <div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-card-value"><?= (int) ($whatsappStats[$key] ?? 0) ?></div><div class="stat-card-label"><?= esc($label) ?></div></div></div>
                    <?php endforeach; ?>
                    <div class="col-12"><p class="text-muted small mb-0">Aberturas e cliques são exclusivos do e-mail; entregas e leituras vêm dos eventos de status da Meta.</p></div>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
