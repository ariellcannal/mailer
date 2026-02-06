/**
 * Gerenciamento de agendamento de importações da Receita Federal
 */
(function() {
    'use strict';
    
    // Buscar base URL do backend
    const baseUrl = document.querySelector('base')?.href || window.location.origin + '/';
    
    $(document).ready(function() {
        initSelect2();
        initFormSubmit();
        
        // Aguardar Select2 inicializar antes de carregar dados
        setTimeout(function() {
            loadDuplicateTask();
        }, 500);
    });
    
    /**
     * Carrega dados de tarefa duplicada do sessionStorage
     */
    function loadDuplicateTask() {
        const duplicateData = sessionStorage.getItem('duplicateTask');
        if (duplicateData) {
            try {
                const task = JSON.parse(duplicateData);
                
                // Preencher nome da tarefa
                if (task.name) {
                    $('#task_name').val(task.name + ' (Cópia)');
                }
                
                // Preencher CNAEs
                if (task.cnaes) {
                    let cnaes = task.cnaes;
                    // Parsear se for string JSON
                    if (typeof cnaes === 'string') {
                        try {
                            cnaes = JSON.parse(cnaes);
                        } catch (e) {
                            cnaes = cnaes.split(',');
                        }
                    }
                    
                    if (Array.isArray(cnaes)) {
                        cnaes.forEach(function(cnae) {
                            const option = new Option(cnae, cnae, true, true);
                            $('#cnaes_select').append(option);
                        });
                        $('#cnaes_select').trigger('change');
                    }
                }
                
                // Preencher UFs
                if (task.ufs) {
                    let ufs = task.ufs;
                    // Parsear se for string JSON
                    if (typeof ufs === 'string') {
                        try {
                            ufs = JSON.parse(ufs);
                        } catch (e) {
                            ufs = ufs.split(',');
                        }
                    }
                    
                    if (Array.isArray(ufs)) {
                        $('#ufs_select').val(ufs).trigger('change');
                    }
                }
                
                // Preencher Situações Fiscais
                if (task.situacoes_fiscais) {
                    const situacoes = task.situacoes_fiscais.split(',');
                    $('#situacoes_select').val(situacoes).trigger('change');
                }
                
                // Limpar sessionStorage
                sessionStorage.removeItem('duplicateTask');
                
                alertify.success('Dados da tarefa carregados. Ajuste conforme necessário e clique em Agendar.');
                
            } catch (e) {
                console.error('Erro ao carregar dados da tarefa:', e);
            }
        }
    }
    
    /**
     * Inicializa Select2 para CNAEs e UFs
     */
    function initSelect2() {
        // Select2 para CNAEs com AJAX
        $('#cnaes_select').select2({
            theme: 'bootstrap-5',
            ajax: {
                url: baseUrl + 'receita/buscarCnaes',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            placeholder: 'Digite para buscar CNAEs',
            minimumInputLength: 2,
            allowClear: true,
            language: {
                inputTooShort: function() {
                    return 'Digite pelo menos 2 caracteres';
                },
                searching: function() {
                    return 'Buscando...';
                },
                noResults: function() {
                    return 'Nenhum resultado encontrado';
                }
            }
        });
        
        // Select2 para UFs
        $('#ufs_select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Selecione os estados',
            allowClear: true,
            language: {
                noResults: function() {
                    return 'Nenhum resultado encontrado';
                }
            }
        });
        
        // Select2 para Situações Fiscais
        $('#situacoes_select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Selecione as situações fiscais',
            allowClear: true,
            language: {
                noResults: function() {
                    return 'Nenhum resultado encontrado';
                }
            }
        });
    }
    
    /**
     * Inicializa submit do formulário
     */
    function initFormSubmit() {
        $('#form-import').on('submit', function(e) {
            e.preventDefault();
            
            const $btn = $('#btn-schedule');
            const originalText = $btn.html();
            
            // Desabilitar botão
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Agendando...');
            
            // Coletar dados
            const formData = {
                task_name: $('#task_name').val(),
                cnaes: $('#cnaes_select').val() || [],
                ufs: $('#ufs_select').val() || [],
                situacoes: $('#situacoes_select').val() || ['2', '3'] // Padrão: ATIVA e SUSPENSA
            };
            
            // Enviar via AJAX
            $.ajax({
                url: baseUrl + 'receita/schedule',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alertify.success('Tarefa agendada com sucesso!');
                        
                        // Redirecionar para página de tarefas após 1 segundo
                        setTimeout(function() {
                            window.location.href = baseUrl + 'receita/tasks';
                        }, 1000);
                    } else {
                        // Mostrar erro detalhado para debug
                        let errorMsg = response.message || 'Erro ao agendar tarefa';
                        if (response.error_detail) {
                            errorMsg += '\n\nDetalhes: ' + response.error_detail;
                            if (response.file) {
                                errorMsg += '\nArquivo: ' + response.file + ':' + response.line;
                            }
                        }
                        alertify.error(errorMsg);
                        console.error('Erro completo:', response);
                        $btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    alertify.error('Erro ao agendar tarefa: ' + error);
                    console.error('XHR error:', xhr.responseText);
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
    }
})();
