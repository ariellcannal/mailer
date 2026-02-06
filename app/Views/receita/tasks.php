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
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <div class="progress" style="height: 20px; width: 200px;">
                                            <div class="progress-bar <?= $task['status'] === 'concluida' ? 'bg-success' : '' ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= $progress ?>%"
                                                 aria-valuenow="<?= $progress ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted text-nowrap"><?= $progress ?>%</small>
                                    </div>
                                    <small class="text-muted">
                                        <?= number_format($task['imported_lines'] ?? 0) ?> registros importados
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $task['processed_files'] ?> / <?= $task['total_files'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $filtros = [];
                                    
                                    // CNAEs (pode vir como JSON)
                                    if (!empty($task['cnaes'])) {
                                        $cnaes = json_decode($task['cnaes'], true);
                                        if (!is_array($cnaes)) {
                                            $cnaes = explode(',', $task['cnaes']);
                                        }
                                        if (!empty($cnaes)) {
                                            $filtros[] = '<strong>CNAEs:</strong> ' . implode(', ', array_map('esc', $cnaes));
                                        }
                                    }
                                    
                                    // Estados (campo 'ufs' no banco, pode vir como JSON)
                                    if (!empty($task['ufs'])) {
                                        $estados = json_decode($task['ufs'], true);
                                        if (!is_array($estados)) {
                                            $estados = explode(',', $task['ufs']);
                                        }
                                        if (!empty($estados)) {
                                            $filtros[] = '<strong>Estados:</strong> ' . implode(', ', array_map('esc', $estados));
                                        }
                                    }
                                    
                                    // Situações Fiscais
                                    if (!empty($task['situacoes_fiscais'])) {
                                        $situacoes = explode(',', $task['situacoes_fiscais']);
                                        $situacoesLabel = [
                                            '1' => 'NULA',
                                            '2' => 'ATIVA',
                                            '3' => 'SUSPENSA',
                                            '4' => 'INAPTA',
                                            '8' => 'BAIXADA'
                                        ];
                                        $situacoesTexto = array_map(function($s) use ($situacoesLabel) {
                                            return $situacoesLabel[$s] ?? $s;
                                        }, $situacoes);
                                        $filtros[] = '<strong>Situações:</strong> ' . implode(', ', $situacoesTexto);
                                    }
                                    
                                    if (empty($filtros)) {
                                        echo '<small class="text-muted">Sem filtros</small>';
                                    } else {
                                        echo '<small>' . implode('<br>', $filtros) . '</small>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($task['created_at']): ?>
                                        <?= date('d/m/Y H:i', strtotime($task['created_at'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($task['status'] === 'em_andamento'): ?>
                                        <button type="button" 
                                                class="btn btn-outline-warning btn-pause" 
                                                data-task-id="<?= $task['id'] ?>"
                                                title="Pausar tarefa">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                        <?php elseif ($task['status'] !== 'concluida' && $task['status'] !== 'erro'): ?>
                                        <button type="button" 
                                                class="btn btn-outline-success btn-start" 
                                                data-task-id="<?= $task['id'] ?>"
                                                title="Iniciar tarefa">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="btn btn-outline-info btn-restart" 
                                                data-task-id="<?= $task['id'] ?>"
                                                title="Reiniciar tarefa">
                                            <i class="fas fa-redo"></i>
                                        </button>
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
