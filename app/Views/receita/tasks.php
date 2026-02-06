<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= $this->include('receita/_toolbar') ?>
    
    <h4 class="mb-4"><i class="fas fa-tasks me-2"></i>Tarefas de Importação</h4>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tasks-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%">Nome</th>
                            <th width="8%">Status</th>
                            <th width="20%">Progresso</th>
                            <th width="8%">Arquivos</th>
                            <th width="20%">Filtros</th>
                            <th width="12%">Agendado em</th>
                            <th width="12%" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Nenhuma tarefa encontrada
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                                <?= view('receita/partials/_task_row', ['task' => $task]) ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php if (!empty($tasks)): ?>
    <div class="mt-3">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            A tabela é atualizada automaticamente a cada 10 segundos
        </small>
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/receita-tasks.js') ?>" defer></script>
<?= $this->endSection() ?>
