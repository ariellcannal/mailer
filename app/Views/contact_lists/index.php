<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 text-uppercase"><i class="fas fa-list-ul text-secondary me-2"></i> Listas de Contatos</h4>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-info btn-sm" id="btn-refresh-all">
            <i class="fas fa-sync-alt"></i> Atualizar Contadores
        </button>
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
                        <th class="text-end" style="width: 200px;">Ações</th>
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
                            <div class="btn-toolbar justify-content-end gap-1" role="toolbar">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?= base_url('contact-lists/view/' . $list['id']) ?>" 
                                       class="btn btn-outline-primary" 
                                       title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('contact-lists/edit/' . $list['id']) ?>" 
                                       class="btn btn-outline-secondary" 
                                       title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-info btn-refresh-single" 
                                            data-list-id="<?= $list['id'] ?>"
                                            title="Atualizar contador">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-delete" 
                                            data-list-id="<?= $list['id'] ?>"
                                            data-list-name="<?= esc($list['name']) ?>"
                                            title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = document.querySelector('base')?.href || window.location.origin + '/';
    
    // Atualizar todas as listas
    document.getElementById('btn-refresh-all')?.addEventListener('click', function() {
        const btn = this;
        const originalHtml = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Atualizando...';
        
        fetch(baseUrl + 'contact-lists/refresh-counts', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertify.success(data.message);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alertify.error(data.message || 'Erro ao atualizar contadores');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alertify.error('Erro ao atualizar contadores');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    });
    
    // Atualizar lista individual
    document.querySelectorAll('.btn-refresh-single').forEach(btn => {
        btn.addEventListener('click', function() {
            const listId = this.dataset.listId;
            const originalHtml = this.innerHTML;
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch(baseUrl + 'contact-lists/refresh-counts/' + listId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alertify.success(data.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alertify.error(data.message || 'Erro ao atualizar contador');
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alertify.error('Erro ao atualizar contador');
                this.disabled = false;
                this.innerHTML = originalHtml;
            });
        });
    });
    
    // Excluir lista
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const listId = this.dataset.listId;
            const listName = this.dataset.listName;
            
            if (!confirm(`Tem certeza que deseja excluir a lista "${listName}"?`)) {
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = baseUrl + 'contact-lists/delete/' + listId;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '<?= csrf_token() ?>';
            csrf.value = '<?= csrf_hash() ?>';
            form.appendChild(csrf);
            
            document.body.appendChild(form);
            form.submit();
        });
    });
});
</script>
<?= $this->endSection() ?>
