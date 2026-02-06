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
                if (response.success && response.html !== undefined) {
                    updateTable(response.html);
                }
            },
            error: function() {
                console.error('Erro ao atualizar tabela');
            }
        });
    }
    
    /**
     * Atualiza conteúdo da tabela com HTML renderizado pelo servidor
     */
    function updateTable(html) {
        const tbody = $('#tasks-table tbody');
        
        if (!html || html.trim() === '') {
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
        
        tbody.html(html);
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
        // Validar que taskId é numérico
        taskId = parseInt(taskId, 10);
        if (isNaN(taskId) || taskId <= 0) {
            alertify.error('ID de tarefa inválido');
            return;
        }
        
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
