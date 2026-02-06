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
        // Pausar tarefa
        $(document).on('click', '.btn-pause', function() {
            const taskId = $(this).data('task-id');
            pauseTask(taskId);
        });
        
        // Iniciar tarefa
        $(document).on('click', '.btn-start', function() {
            const taskId = $(this).data('task-id');
            startTask(taskId);
        });
        
        // Reiniciar tarefa
        $(document).on('click', '.btn-restart', function() {
            const taskId = $(this).data('task-id');
            restartTask(taskId);
        });
        
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
                    <td colspan="8" class="text-center text-muted py-4">
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
                            <div class="progress" style="height: 20px; width: 200px;">
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
                        ${buildFiltrosHtml(task)}
                    </td>
                    <td>
                        ${task.created_at ? formatDateTime(task.created_at) : ''}
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            ${task.status === 'em_andamento' ? `
                            <button type="button" 
                                    class="btn btn-outline-warning btn-pause" 
                                    data-task-id="${task.id}"
                                    title="Pausar tarefa">
                                <i class="fas fa-pause"></i>
                            </button>
                            ` : (task.status !== 'concluida' && task.status !== 'erro' ? `
                            <button type="button" 
                                    class="btn btn-outline-success btn-start" 
                                    data-task-id="${task.id}"
                                    title="Iniciar tarefa">
                                <i class="fas fa-play"></i>
                            </button>
                            ` : '')}
                            <button type="button" 
                                    class="btn btn-outline-info btn-restart" 
                                    data-task-id="${task.id}"
                                    title="Reiniciar tarefa">
                                <i class="fas fa-redo"></i>
                            </button>
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
     * Constrói HTML dos filtros
     */
    function buildFiltrosHtml(task) {
        const filtros = [];
        
        // CNAEs (pode vir como JSON ou string)
        if (task.cnaes) {
            let cnaes = [];
            try {
                // Tentar parsear como JSON
                cnaes = typeof task.cnaes === 'string' ? JSON.parse(task.cnaes) : task.cnaes;
            } catch (e) {
                // Se não for JSON, assumir que é string separada por vírgula
                cnaes = task.cnaes.split(',');
            }
            if (cnaes.length > 0) {
                filtros.push(`<strong>CNAEs:</strong> ${cnaes.map(escapeHtml).join(', ')}`);
            }
        }
        
        // Estados (campo 'ufs' no banco, pode vir como JSON ou string)
        if (task.ufs) {
            let estados = [];
            try {
                // Tentar parsear como JSON
                estados = typeof task.ufs === 'string' ? JSON.parse(task.ufs) : task.ufs;
            } catch (e) {
                // Se não for JSON, assumir que é string separada por vírgula
                estados = task.ufs.split(',');
            }
            if (estados.length > 0) {
                filtros.push(`<strong>Estados:</strong> ${estados.map(escapeHtml).join(', ')}`);
            }
        }
        
        // Situações Fiscais
        if (task.situacoes_fiscais) {
            const situacoes = task.situacoes_fiscais.split(',');
            const situacoesLabel = {
                '1': 'NULA',
                '2': 'ATIVA',
                '3': 'SUSPENSA',
                '4': 'INAPTA',
                '8': 'BAIXADA'
            };
            const situacoesTexto = situacoes.map(s => situacoesLabel[s] || s);
            filtros.push(`<strong>Situações:</strong> ${situacoesTexto.join(', ')}`);
        }
        
        if (filtros.length === 0) {
            return '<small class="text-muted">Sem filtros</small>';
        }
        
        return '<small>' + filtros.join('<br>') + '</small>';
    }
    
    /**
     * Inicia tarefa
     */
    function startTask(taskId) {
        alertify.confirm(
            'Iniciar Tarefa',
            'Deseja iniciar esta tarefa? Outras tarefas em andamento serão pausadas.',
            function() {
                $.ajax({
                    url: baseUrl + 'receita/start-task/' + taskId,
                    method: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alertify.success('Tarefa iniciada com sucesso');
                            refreshTable();
                        } else {
                            alertify.error(response.message || 'Erro ao iniciar tarefa');
                        }
                    },
                    error: function() {
                        alertify.error('Erro ao iniciar tarefa');
                    }
                });
            },
            function() {}
        );
    }
    
    /**
     * Reinicia tarefa
     */
    function restartTask(taskId) {
        alertify.confirm(
            'Reiniciar Tarefa',
            'Deseja reiniciar esta tarefa? Todo o progresso será perdido e a tarefa voltará ao início.',
            function() {
                $.ajax({
                    url: baseUrl + 'receita/restart-task/' + taskId,
                    method: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alertify.success('Tarefa reiniciada com sucesso');
                            refreshTable();
                        } else {
                            alertify.error(response.message || 'Erro ao reiniciar tarefa');
                        }
                    },
                    error: function() {
                        alertify.error('Erro ao reiniciar tarefa');
                    }
                });
            },
            function() {}
        );
    }
    
    /**
     * Pausa tarefa
     */
    function pauseTask(taskId) {
        alertify.confirm(
            'Pausar Tarefa',
            'Deseja pausar esta tarefa? Ela poderá ser retomada posteriormente.',
            function() {
                $.ajax({
                    url: baseUrl + 'receita/pause-task/' + taskId,
                    method: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alertify.success('Tarefa pausada com sucesso');
                            refreshTable();
                        } else {
                            alertify.error(response.message || 'Erro ao pausar tarefa');
                        }
                    },
                    error: function() {
                        alertify.error('Erro ao pausar tarefa');
                    }
                });
            },
            function() {}
        );
    }
    
    /**
     * Carrega dados da tarefa para duplicação (redireciona ao formulário)
     */
    function duplicateTask(taskId) {
        $.ajax({
            url: baseUrl + 'receita/duplicate-task/' + taskId,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Armazenar dados no sessionStorage
                    sessionStorage.setItem('duplicateTask', JSON.stringify(response.task));
                    // Redirecionar para o formulário
                    window.location.href = baseUrl + 'receita';
                } else {
                    alertify.error(response.message || 'Erro ao carregar tarefa');
                }
            },
            error: function() {
                alertify.error('Erro ao carregar tarefa');
            }
        });
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
