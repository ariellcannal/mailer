<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="fas fa-users"></i> Contatos</h4>
            <div class="d-flex gap-2">
                <a href="<?= base_url('contact-lists') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-list-ul"></i> Listas
                </a>
                <a href="<?= base_url('contacts/import') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-file-upload"></i> Importar
                </a>
                <a href="<?= base_url('contacts/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Novo Contato
                </a>
            </div>
        </div>

        <?php if (session('contacts_success')): ?>
            <div class="alert alert-success"><?= esc(session('contacts_success')) ?></div>
        <?php endif; ?>
        <?php if (session('contacts_error')): ?>
            <div class="alert alert-danger"><?= esc(session('contacts_error')) ?></div>
        <?php endif; ?>

        <form method="GET" action="<?= base_url('contacts') ?>" class="row g-2 mb-4 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filtrar por e-mail</label>
                <input type="text" name="email" value="<?= esc($filters['email']) ?>" class="form-control" placeholder="email@dominio.com">
            </div>
            <div class="col-md-4">
                <label class="form-label">Filtrar por nome</label>
                <input type="text" name="name" value="<?= esc($filters['name']) ?>" class="form-control" placeholder="Nome do contato">
            </div>
            <div class="col-md-2">
                <label class="form-label">Qualidade</label>
                <select name="quality_score" class="form-select">
                    <option value="">Todas</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= (string) $filters['quality_score'] === (string) $i ? 'selected' : '' ?>><?= $i ?> estrela(s)</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="<?= base_url('contacts') ?>" class="btn btn-outline-secondary" title="Limpar filtros">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>

        <?php if (!empty($lists)): ?>
        <form id="bulkListsForm" action="<?= base_url('contacts/bulk-assign') ?>" method="POST" class="mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="select_all" id="selectAllFlag" value="0">
            <input type="hidden" name="filters[email]" value="<?= esc($filters['email']) ?>">
            <input type="hidden" name="filters[name]" value="<?= esc($filters['name']) ?>">
            <input type="hidden" name="filters[quality_score]" value="<?= esc($filters['quality_score']) ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Adicionar selecionados às listas</label>
                    <select name="lists[]" class="form-select select2" multiple data-placeholder="Selecione as listas">
                        <?php foreach ($lists as $list): ?>
                            <option value="<?= $list['id'] ?>"><?= esc($list['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary mt-4">
                        <i class="fas fa-check"></i> Adicionar
                    </button>
                    <div class="mt-4 text-muted small">Selecione os contatos na tabela abaixo.</div>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <div class="table-responsive">
            <div id="selectAllNotice" class="alert alert-info d-none">Deseja selecionar todos os <?= (int) $totalContacts ?> contatos? <a href="#" id="confirmSelectAll">Clique aqui</a>.</div>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 60px;">
                            <div class="form-check d-flex justify-content-center mb-0">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </div>
                        </th>
                        <th>Email</th>
                        <th>Nome</th>
                        <th>Apelido</th>
                        <th>Listas</th>
                        <th>Qualidade</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                Nenhum contato encontrado
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="contacts[]" value="<?= $contact['id'] ?>" form="bulkListsForm" class="form-check-input contact-checkbox">
                                </td>
                                <td><?= esc($contact['email']) ?></td>
                                <td><?= esc($contact['name']) ?></td>
                                <td><?= esc($contact['nickname'] ?? '') ?></td>
                                <td>
                                    <?php if (!empty($contactLists[$contact['id']] ?? [])): ?>
                                        <?php foreach ($contactLists[$contact['id']] as $list): ?>
                                            <span class="badge bg-secondary me-1 mb-1"><?= esc($list['name']) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sem listas</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php $starClass = $i <= (int) $contact['quality_score'] ? 'text-warning' : 'text-muted'; ?>
                                        <i class="fas fa-star <?= $starClass ?>"></i>
                                    <?php endfor; ?>
                                </td>
                                <td>
                                    <?php if ((int) $contact['is_active'] === 1): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('contacts/view/' . $contact['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    <a href="<?= base_url('contacts/edit/' . $contact['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
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
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/contacts-form.js') ?>" defer></script>
<script src="<?= base_url('assets/js/contacts-index.js') ?>" defer></script>
<?= $this->endSection() ?>
