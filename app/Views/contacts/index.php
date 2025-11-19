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

        <?php if (!empty($lists)): ?>
        <form id="bulkListsForm" action="<?= base_url('contacts/bulk-assign') ?>" method="POST" class="mb-3">
            <?= csrf_field() ?>
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
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 60px;"><input type="checkbox" id="selectAll"></th>
                        <th>Email</th>
                        <th>Nome</th>
                        <th>Listas</th>
                        <th>Qualidade</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                Nenhum contato encontrado
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="contacts[]" value="<?= $contact['id'] ?>" form="bulkListsForm" class="form-check-input">
                                </td>
                                <td><?= esc($contact['email']) ?></td>
                                <td><?= esc($contact['name']) ?></td>
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
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('contacts/edit/' . $contact['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?= $pager->links() ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function() {
        $('.select2').each(function() {
            const placeholder = $(this).data('placeholder') || 'Selecione';
            $(this).select2({
                width: '100%',
                placeholder: placeholder,
                allowClear: true
            });
        });

        $('#selectAll').on('change', function() {
            const checked = $(this).is(':checked');
            $('input[name="contacts[]"]').prop('checked', checked);
        });
    });
</script>
<?= $this->endSection() ?>
