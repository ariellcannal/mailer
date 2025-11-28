<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 text-uppercase"><i class="fas fa-list-ul text-secondary me-2"></i> Listas de Contatos</h4>
    <div class="d-flex gap-2">
        <a href="<?= base_url('contact-lists/create') ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nova lista
        </a>
        <a href="<?= base_url('contacts') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-users"></i> Voltar para contatos
        </a>
    </div>
</div>

<?php if (session('contact_lists_success')): ?>
    <div class="alert alert-success"><?= esc(session('contact_lists_success')) ?></div>
<?php endif; ?>
<?php if (session('contact_lists_error')): ?>
    <div class="alert alert-danger"><?= esc(session('contact_lists_error')) ?></div>
<?php endif; ?>

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
                            <a href="<?= base_url('contact-lists/view/' . $list['id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= base_url('contact-lists/edit/' . $list['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form action="<?= base_url('contact-lists/delete/' . $list['id']) ?>" method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover esta lista?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
