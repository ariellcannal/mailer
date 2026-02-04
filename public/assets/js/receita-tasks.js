/**
 * Gerenciamento de tarefas de importação da Receita Federal
 */
(function() {
    'use strict';
    
    // Buscar base URL do backend
    const baseUrl = document.querySelector('base')?.href || window.location.origin + '/';
    
    let autoRefreshInterval = null;
    
    $(document).ready(function() {
        initEventHandlers();
        startAutoRefresh();
    });
    
    /**
     * Inicializa event handlers
     */
    function initEventHandlers() {
        // Duplicar tarefa
        $(document).on('click', '.btn-duplicate', function() {
            const taskId = $(this).data('task-id');
            duplicateTask(taskId);
        });
        
        // Excluir tarefa
        $(document).on('click', '.btn-delete', function() {
            const taskId = $(this).data('task-id');
            deleteTask(taskId);
        });
    }
    
    /**
     * Inicia atualização automática da tabela
     */
    function startAutoRefresh() {
        // Atualizar a cada 10 segundos
        autoRefreshInterval = setInterval(function() {
            refreshTable();
        }, 10000);
    }
    
    /**
     * Atualiza tabela via AJAX
     */
    function refreshTable() {
        $.ajax({
            url: baseUrl + 'receita/tasks-data',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.tasks) {
                    updateTable(response.tasks);
                }
            },
            error: function() {
                console.error('Erro ao atualizar tabela');
            }
        });
    }
    
    /**
     * Atualiza conteúdo da tabela
     */
    function updateTable(tasks) {
        const tbody = $('#tasks-table tbody');
        
        if (tasks.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        Nenhuma tarefa encontrada
                    </td>
                </tr>
            `);
            return;
        }
        
        tbody.empty();
        
        tasks.forEach(function(task) {
            // Progresso baseado em bytes processados (fallback para arquivos)
            let progress = 0;
            if (task.total_bytes && task.total_bytes > 0) {
                progress = Math.round((task.processed_bytes / task.total_bytes) * 100 * 100) / 100;
            } else if (task.total_files && task.total_files > 0) {
                progress = Math.round((task.processed_files / task.total_files) * 100 * 100) / 100;
            }
            
            const statusClass = {
                'agendada': 'secondary',
                'em_andamento': 'primary',
                'concluida': 'success',
                'erro': 'danger'
            };
            
            const statusLabel = {
                'agendada': 'Agendada',
                'em_andamento': 'Em Andamento',
                'concluida': 'Concluída',
                'erro': 'Erro'
            };
            
            let row = `
                <tr data-task-id="${task.id}">
                    <td>${task.id}</td>
                    <td>
                        <strong>${escapeHtml(task.name || 'Importação #' + task.id)}</strong>
                        ${task.current_file ? `<br><small class="text-muted"><i class="fas fa-file-archive me-1"></i>${escapeHtml(task.current_file)}</small>` : ''}
                    </td>
                    <td>
                        <span class="badge bg-${statusClass[task.status]}">
                            ${statusLabel[task.status]}
                        </span>
                        ${task.status === 'erro' && task.error_message ? `<br><small class="text-danger" title="${escapeHtml(task.error_message)}"><i class="fas fa-exclamation-circle"></i> Ver erro</small>` : ''}
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <div class="progress flex-grow-1" style="height: 20px;">
                                <div class="progress-bar ${task.status === 'concluida' ? 'bg-success' : ''}" 
                                     role="progressbar" 
                                     style="width: ${progress}%"
                                     aria-valuenow="${progress}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted text-nowrap">${progress}%</small>
                        </div>
                        <small class="text-muted">
                            ${formatNumber(task.imported_lines || 0)} registros importados
                        </small>
                    </td>
                    <td>
                        <span class="badge bg-info">
                            ${task.processed_files} / ${task.total_files}
                        </span>
                    </td>
                    <td>
                        ${task.created_at ? formatDateTime(task.created_at) : ''}
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button" 
                                    class="btn btn-outline-primary btn-duplicate" 
                                    data-task-id="${task.id}"
                                    title="Duplicar tarefa">
                                <i class="fas fa-copy"></i>
                            </button>
                            ${task.status === 'agendada' ? `
                            <button type="button" 
                                    class="btn btn-outline-danger btn-delete" 
                                    data-task-id="${task.id}"
                                    title="Excluir tarefa">
                                <i class="fas fa-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
            
            tbody.append(row);
        });
    }
    
    /**
     * Duplica tarefa
     */
    function duplicateTask(taskId) {
        alertify.confirm(
            'Duplicar Tarefa',
            'Deseja duplicar esta tarefa? Os filtros e configurações serão copiados.',
            function() {
                $.ajax({
                    url: baseUrl + 'receita/duplicate-task/' + taskId,
                    method: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alertify.success('Tarefa duplicada com sucesso');
                            refreshTable();
                        } else {
                            alertify.error(response.message || 'Erro ao duplicar tarefa');
                        }
                    },
                    error: function() {
                        alertify.error('Erro ao duplicar tarefa');
                    }
                });
            },
            function() {}
        );
    }
    
    /**
     * Exclui tarefa
     */
    function deleteTask(taskId) {
        alertify.confirm(
            'Excluir Tarefa',
            'Tem certeza que deseja excluir esta tarefa? Esta ação não pode ser desfeita.',
            function() {
                $.ajax({
                    url: baseUrl + 'receita/delete-task/' + taskId,
                    method: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alertify.success('Tarefa excluída com sucesso');
                            refreshTable();
                        } else {
                            alertify.error(response.message || 'Erro ao excluir tarefa');
                        }
                    },
                    error: function() {
                        alertify.error('Erro ao excluir tarefa');
                    }
                });
            },
            function() {}
        );
    }
    
    /**
     * Formata número
     */
    function formatNumber(num) {
        return new Intl.NumberFormat('pt-BR').format(num);
    }
    
    /**
     * Formata data/hora
     */
    function formatDateTime(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    /**
     * Escapa HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
    
    // Limpar interval ao sair da página
    $(window).on('beforeunload', function() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
    });
})();
