<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-tasks me-2"></i>Tarefas de Importação</h4>
        <a href="<?= base_url('receita') ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nova Importação
        </a>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="tasks-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="20%">Nome</th>
                            <th width="10%">Status</th>
                            <th width="30%">Progresso</th>
                            <th width="10%">Arquivos</th>
                            <th width="15%">Agendado em</th>
                            <th width="10%" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Nenhuma tarefa encontrada
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                            <?php
                                // Progresso baseado em bytes processados (fallback para arquivos se bytes não existir)
                                $progress = 0;
                                if (isset($task['total_bytes']) && $task['total_bytes'] > 0) {
                                    $progress = round(($task['processed_bytes'] / $task['total_bytes']) * 100, 2);
                                } elseif (isset($task['total_files']) && $task['total_files'] > 0) {
                                    $progress = round(($task['processed_files'] / $task['total_files']) * 100, 2);
                                }
                                
                                $statusClass = [
                                    'agendada' => 'secondary',
                                    'em_andamento' => 'primary',
                                    'concluida' => 'success',
                                    'erro' => 'danger'
                                ];
                                
                                $statusLabel = [
                                    'agendada' => 'Agendada',
                                    'em_andamento' => 'Em Andamento',
                                    'concluida' => 'Concluída',
                                    'erro' => 'Erro'
                                ];
                            ?>
                            <tr data-task-id="<?= $task['id'] ?>">
                                <td><?= $task['id'] ?></td>
                                <td>
                                    <strong><?= esc($task['name'] ?: 'Importação #' . $task['id']) ?></strong>
                                    <?php if (!empty($task['current_file'])): ?>
                                    <br><small class="text-muted">
                                        <i class="fas fa-file-archive me-1"></i><?= esc($task['current_file']) ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $statusClass[$task['status']] ?>">
                                        <?= $statusLabel[$task['status']] ?>
                                    </span>
                                    <?php if ($task['status'] === 'erro' && !empty($task['error_message'])): ?>
                                    <br><small class="text-danger" title="<?= esc($task['error_message']) ?>">
                                        <i class="fas fa-exclamation-circle"></i> Ver erro
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="progress mb-1" style="height: 20px;">
                                        <div class="progress-bar <?= $task['status'] === 'concluida' ? 'bg-success' : '' ?>" 
                                             role="progressbar" 
                                             style="width: <?= $progress ?>%"
                                             aria-valuenow="<?= $progress ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $progress ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?= number_format($task['imported_lines']) ?> registros importados
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $task['processed_files'] ?> / <?= $task['total_files'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($task['created_at']): ?>
                                        <?= date('d/m/Y H:i', strtotime($task['created_at'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" 
                                                class="btn btn-outline-primary btn-duplicate" 
                                                data-task-id="<?= $task['id'] ?>"
                                                title="Duplicar tarefa">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <?php if ($task['status'] === 'agendada'): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-delete" 
                                                data-task-id="<?= $task['id'] ?>"
                                                title="Excluir tarefa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
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
