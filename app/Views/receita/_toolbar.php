<div class="receita-toolbar mb-4 d-flex justify-content-end">
    <div class="btn-group" role="group">
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
        <a href="<?= base_url('receita') ?>" class="btn btn-outline-primary <?= ($activeMenu ?? '') === 'receita-index' ? 'active' : '' ?>">
            <i class="fas fa-plus me-2"></i>Nova Importação
        </a>
        <a href="<?= base_url('receita/tasks') ?>" class="btn btn-outline-info <?= ($activeMenu ?? '') === 'receita-tasks' ? 'active' : '' ?>">
            <i class="fas fa-list me-2"></i>Ver Tarefas
        </a>
        <a href="<?= base_url('receita/empresas') ?>" class="btn btn-outline-success <?= ($activeMenu ?? '') === 'receita-empresas' ? 'active' : '' ?>">
            <i class="fas fa-building me-2"></i>Registros Importados
        </a>
    </div>
</div>
