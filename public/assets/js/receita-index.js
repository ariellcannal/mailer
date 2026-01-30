/**
 * Receita Federal Import Page
 * Gerencia importação de dados da Receita Federal com streaming
 */
(function() {
    'use strict';

    let abortController = null;

    // Buscar base URL do backend
    const baseUrl = document.querySelector('base')?.href || window.location.origin + '/';

    $(document).ready(function() {
        // Inicializa Select2 com AJAX para busca de CNAEs
        $('#cnaes_select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pesquise por código ou descrição do CNAE',
            ajax: {
                url: baseUrl + 'receita/buscarCnaes',
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return { results: data };
                },
                cache: true
            }
        });

        $('#form-import').on('submit', function(e) {
            e.preventDefault();
            startImport();
        });

        $('#btn-stop').on('click', function() {
            stopImport();
        });
    });

    async function startImport() {
        const cnaes = $('#cnaes_select').val() || [];
        const restart = $('#restart_process').is(':checked');

        if (cnaes.length === 0) {
            if (!confirm("Atenção: Nenhum CNAE selecionado. Importar TODOS os dados?")) return;
        }

        const btnStart = $('#btn-start');
        const btnStop = $('#btn-stop');
        const consoleLog = $('#import-console');
        const statusText = $('#process-status');
        
        btnStart.prop('disabled', true).addClass('d-none');
        btnStop.removeClass('d-none');
        statusText.html('<span class="status-badge bg-success"></span> Processando...');
        consoleLog.html('> Conectando ao servidor...\n');

        abortController = new AbortController();
        
        try {
            const url = new URL(baseUrl + 'receita/importar');
            url.searchParams.append('restart', restart);
            cnaes.forEach(c => url.searchParams.append('cnaes[]', c));
            
            const response = await fetch(url, { signal: abortController.signal });
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            
            let partialData = ''; 
            let lastProgressLine = null;

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                
                partialData += decoder.decode(value, { stream: true });
                let lines = partialData.split('\n');
                partialData = lines.pop(); 

                for (let line of lines) {
                    line = line.trim();
                    if (!line || line === "LOG:") continue; // Pula logs vazios que geram linhas brancas

                    if (line.startsWith("BAR:")) {
                        const content = line.replace("BAR:", "").trim();
                        if (!lastProgressLine) {
                            lastProgressLine = $('<div class="progress-line"></div>').appendTo(consoleLog);
                        }
                        lastProgressLine.html(content);
                    }
                    else if (line.startsWith("LOG:")) {
                        const content = line.replace("LOG:", "").trim();
                        if (content) consoleLog.append('<div>' + content + '</div>');
                    }
                }
                consoleLog.scrollTop(consoleLog[0].scrollHeight);
            }

            statusText.text('Concluído');
        } catch (error) {
            if (error.name === 'AbortError') {
                consoleLog.append('<div class="text-warning">> Interrompido pelo usuário.</div>');
            } else {
                consoleLog.append('<div class="text-danger">> Erro: ' + error.message + '</div>');
            }
        } finally {
            btnStart.prop('disabled', false).removeClass('d-none');
            btnStop.addClass('d-none');
            statusText.text('Inativo');
        }
    }

    function stopImport() {
        if (confirm('Deseja realmente interromper o processo atual?')) {
            if (abortController) abortController.abort();
            
            // Chama rota para matar processos PHP no servidor
            $.get(baseUrl + 'receita/parar');
        }
    }

    // Expor funções globalmente se necessário
    window.ReceitaImport = {
        startImport,
        stopImport
    };
})();
