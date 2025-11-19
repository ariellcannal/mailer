<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 text-uppercase"><i class="fas fa-list-ul text-secondary me-2"></i> Listas de Contatos</h4>
    <a href="<?= base_url('contacts') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-users"></i> Voltar para contatos
    </a>
</div>

<?php if (session('contact_lists_success')): ?>
    <div class="alert alert-success"><?= esc(session('contact_lists_success')) ?></div>
<?php endif; ?>
<?php if (session('contact_lists_error')): ?>
    <div class="alert alert-danger"><?= esc(session('contact_lists_error')) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="section-title mb-3 text-uppercase">Nova lista</h5>
                <form action="<?= base_url('contact-lists/store') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Opcional"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Criar lista
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="section-title mb-3 text-uppercase">Listas existentes</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Total</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($lists)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Nenhuma lista cadastrada.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($lists as $list): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($list['name']) ?></td>
                                <td><?= esc($list['description']) ?></td>
                                <td><span class="badge bg-primary"><?= (int) $list['total_contacts'] ?></span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-list-<?= $list['id'] ?>">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="<?= base_url('contact-lists/delete/' . $list['id']) ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover esta lista?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="collapse" id="edit-list-<?= $list['id'] ?>">
                                <td colspan="4">
                                    <form action="<?= base_url('contact-lists/update/' . $list['id']) ?>" method="POST" class="border rounded p-3 bg-light">
                                        <?= csrf_field() ?>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-5">
                                                <label class="form-label">Nome</label>
                                                <input type="text" class="form-control" name="name" value="<?= esc($list['name']) ?>" required>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">Descrição</label>
                                                <input type="text" class="form-control" name="description" value="<?= esc($list['description']) ?>">
                                            </div>
                                            <div class="col-md-2 d-grid">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Salvar
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
