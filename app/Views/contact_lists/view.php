<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 text-uppercase"><i class="fas fa-list-ul text-secondary me-2"></i> <?= esc($list['name']) ?></h4>
    <div class="d-flex gap-2">
        <a href="<?= base_url('contact-lists') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <a href="<?= base_url('contacts/create?list_id=' . $list['id']) ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus"></i> Novo Contato
        </a>
    </div>
</div>

<?php if (session('contact_lists_success')): ?>
    <div class="alert alert-success"><?= esc(session('contact_lists_success')) ?></div>
<?php endif; ?>
<?php if (session('contact_lists_error')): ?>
    <div class="alert alert-danger"><?= esc(session('contact_lists_error')) ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <p class="mb-2 text-muted"><?= esc($list['description'] ?? 'Sem descrição') ?></p>
        <div class="d-flex flex-wrap gap-3">
            <span class="badge bg-primary">Total de contatos: <?= (int) ($list['total_contacts'] ?? 0) ?></span>
            <span class="badge bg-secondary">Criada em <?= date('d/m/Y', strtotime($list['created_at'])) ?></span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="mb-0 text-uppercase">Contatos na lista</h5>
            <form class="d-flex gap-2" method="GET" action="<?= current_url() ?>">
                <input type="hidden" name="page" value="<?= esc($pager->getCurrentPage()) ?>">
                <input type="text" name="email" class="form-control" placeholder="Filtrar por email" value="<?= esc($filters['email']) ?>">
                <input type="text" name="name" class="form-control" placeholder="Filtrar por nome" value="<?= esc($filters['name']) ?>">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </form>
        </div>

        <!-- Formulário de remoção em massa -->
        <form id="bulkRemoveForm" action="<?= base_url('contact-lists/bulk-detach-contacts/' . $list['id']) ?>" method="POST" class="mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="select_all" id="selectAllFlag" value="0">
            <input type="hidden" name="filters[email]" value="<?= esc($filters['email']) ?>">
            <input type="hidden" name="filters[name]" value="<?= esc($filters['name']) ?>">
            
            <button type="submit" class="btn btn-danger btn-sm" id="bulkRemoveBtn" onclick="return confirm('Tem certeza que deseja remover os contatos selecionados desta lista?');">
                <i class="fas fa-trash"></i> Remover Selecionados
            </button>
            <small class="text-muted ms-2">Selecione os contatos na tabela abaixo.</small>
        </form>

        <div class="table-responsive">
            <!-- Aviso de seleção total -->
            <div id="selectAllNotice" class="alert alert-info d-none mb-3">
                Deseja selecionar todos os <?= (int) $totalContacts ?> contatos do filtro? 
                <a href="#" id="confirmSelectAll">Clique aqui</a>.
            </div>
            
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 60px;">
                            <div class="form-check d-flex justify-content-center mb-0">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </div>
                        </th>
                        <th>Email</th>
                        <th>Nome</th>
                        <th>Apelido</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum contato encontrado nesta lista.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="contacts[]" value="<?= $contact['id'] ?>" form="bulkRemoveForm" class="form-check-input contact-checkbox">
                                </td>
                                <td><?= esc($contact['email']) ?></td>
                                <td><?= esc($contact['name']) ?></td>
                                <td><?= esc($contact['nickname'] ?? '') ?></td>
                                <td>
                                    <?php if ((int) $contact['is_active'] === 1): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('contacts/view/' . $contact['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="<?= base_url('contact-lists/detach-contact/' . $list['id'] . '/' . $contact['id']) ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover este contato da lista?');">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?= $pager->links('default', 'bootstrap_full') ?>
    </div>
</div>

<script src="<?= base_url('assets/js/contact-list-view.js') ?>"></script>
<?= $this->endSection() ?>
