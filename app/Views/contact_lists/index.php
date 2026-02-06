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
        
        <!-- Toolbar -->
        <div class="mb-3 d-flex gap-2 align-items-center" id="toolbar">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-view" disabled>
                <i class="fas fa-eye"></i> Visualizar
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-edit" disabled>
                <i class="fas fa-pen"></i> Editar
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-delete" disabled>
                <i class="fas fa-trash"></i> Excluir
            </button>
            <div class="vr"></div>
            <button type="button" class="btn btn-sm btn-outline-info" id="btn-refresh">
                <i class="fas fa-sync-alt"></i> Atualizar Contadores
            </button>
            <small class="text-muted ms-2" id="selection-info">Nenhuma lista selecionada</small>
        </div>
        
        <div class="table-responsive">
            <table class="table align-middle" id="lists-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all" class="form-check-input">
                        </th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Total</th>
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
                        <td>
                            <input type="checkbox" class="form-check-input list-checkbox" value="<?= $list['id'] ?>" data-name="<?= esc($list['name']) ?>">
                        </td>
                        <td class="fw-semibold"><?= esc($list['name']) ?></td>
                        <td><?= esc($list['description']) ?></td>
                        <td><span class="badge bg-primary"><?= (int) $list['total_contacts'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.list-checkbox');
    const btnView = document.getElementById('btn-view');
    const btnEdit = document.getElementById('btn-edit');
    const btnDelete = document.getElementById('btn-delete');
    const btnRefresh = document.getElementById('btn-refresh');
    const selectionInfo = document.getElementById('selection-info');
    
    // Atualizar estado dos botões
    function updateToolbar() {
        const selected = Array.from(checkboxes).filter(cb => cb.checked);
        const count = selected.length;
        
        if (count === 0) {
            btnView.disabled = true;
            btnEdit.disabled = true;
            btnDelete.disabled = true;
            selectionInfo.textContent = 'Nenhuma lista selecionada';
        } else if (count === 1) {
            btnView.disabled = false;
            btnEdit.disabled = false;
            btnDelete.disabled = false;
            selectionInfo.textContent = `1 lista selecionada: ${selected[0].dataset.name}`;
        } else {
            btnView.disabled = true;
            btnEdit.disabled = true;
            btnDelete.disabled = false;
            selectionInfo.textContent = `${count} listas selecionadas`;
        }
    }
    
    // Select all
    selectAll?.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateToolbar();
    });
    
    // Individual checkboxes
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            const someChecked = Array.from(checkboxes).some(c => c.checked);
            if (selectAll) {
                selectAll.checked = allChecked;
                selectAll.indeterminate = someChecked && !allChecked;
            }
            updateToolbar();
        });
    });
    
    // Visualizar
    btnView?.addEventListener('click', function() {
        const selected = Array.from(checkboxes).find(cb => cb.checked);
        if (selected) {
            window.location.href = `<?= base_url('contact-lists/view/') ?>${selected.value}`;
        }
    });
    
    // Editar
    btnEdit?.addEventListener('click', function() {
        const selected = Array.from(checkboxes).find(cb => cb.checked);
        if (selected) {
            window.location.href = `<?= base_url('contact-lists/edit/') ?>${selected.value}`;
        }
    });
    
    // Excluir
    btnDelete?.addEventListener('click', function() {
        const selected = Array.from(checkboxes).filter(cb => cb.checked);
        if (selected.length === 0) return;
        
        const names = selected.map(cb => cb.dataset.name).join(', ');
        const confirmMsg = selected.length === 1 
            ? `Tem certeza que deseja excluir a lista "${names}"?`
            : `Tem certeza que deseja excluir ${selected.length} listas?`;
        
        if (!confirm(confirmMsg)) return;
        
        // Excluir uma por vez (pode ser otimizado para batch delete)
        let completed = 0;
        selected.forEach(cb => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `<?= base_url('contact-lists/delete/') ?>${cb.value}`;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '<?= csrf_token() ?>';
            csrf.value = '<?= csrf_hash() ?>';
            form.appendChild(csrf);
            
            document.body.appendChild(form);
            form.submit();
        });
    });
    
    // Atualizar contadores
    btnRefresh?.addEventListener('click', function() {
        const selected = Array.from(checkboxes).filter(cb => cb.checked);
        const btn = this;
        const originalHtml = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Atualizando...';
        
        const url = selected.length === 1 
            ? `<?= base_url('contact-lists/refresh-counts/') ?>${selected[0].value}`
            : `<?= base_url('contact-lists/refresh-counts') ?>`;
        
        fetch(url, {
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
});
</script>
<?= $this->endSection() ?>
