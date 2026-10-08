<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $isEdit = !empty($contact); ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4"><i class="fas <?= $isEdit ? 'fa-edit' : 'fa-user-plus' ?>"></i> <?= $isEdit ? 'Editar Contato' : 'Novo Contato' ?></h4>

        <form action="<?= $isEdit ? base_url('contacts/update/' . $contact['id']) : base_url('contacts/store') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">E-mail*</label>
                <input type="email" class="form-control" name="email" value="<?= esc(old('email', $contact['email'] ?? '')) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label" for="contactPhone">Telefone WhatsApp</label>
                <input type="tel" id="contactPhone" class="form-control" name="phone" value="<?= esc(old('phone', $contact['phone'] ?? '')) ?>" placeholder="(11) 99999-9999" inputmode="tel" maxlength="15" data-br-phone-mask>
                <small class="text-muted">DDD + telefone com 8 ou 9 dígitos. O código do Brasil (55) é aplicado ao salvar.</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" class="form-control" name="name" value="<?= esc(old('name', $contact['name'] ?? '')) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Apelido</label>
                <input type="text" class="form-control" name="nickname" value="<?= esc(old('nickname', $contact['nickname'] ?? '')) ?>" placeholder="Primeiro nome ou apelido preferido">
                <small class="text-muted">Usado para personalizar envios quando disponível.</small>
            </div>

            <?php if (!empty($lists)): ?>
            <div class="mb-3">
                <label class="form-label">Listas</label>
                <select name="lists[]" class="form-control" multiple data-placeholder="Selecione as listas">
                    <?php foreach ($lists as $list): ?>
                        <?php $isSelected = in_array($list['id'], old('lists', $selectedLists ?? [])); ?>
                        <option value="<?= $list['id'] ?>" <?= $isSelected ? 'selected' : '' ?>><?= esc($list['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="btn-group" role="group" aria-label="Ações do formulário">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <a href="<?= base_url('contacts') ?>" class="btn btn-neutral">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>


