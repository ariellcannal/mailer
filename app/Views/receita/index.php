<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    #import-console { background: #1e1e1e; color: #00ff00; font-family: 'Courier New', monospace; height: 400px; overflow-y: auto; padding: 15px; border-radius: 5px; font-size: 0.9rem; }
    .status-badge { width: 15px; height: 15px; display: inline-block; border-radius: 50%; margin-right: 5px; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Configuração de Importação</h6>
                </div>
                <div class="card-body">
                    <form id="form-import">
                        <div class="mb-3">
                            <label class="form-label">Filtrar por CNAEs (opcional)</label>
                            <select id="cnaes_select" name="cnaes[]" class="form-control" multiple></select>
                            <small class="text-muted">Se vazio, importará todos os CNAEs.</small>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="restart_process">
                            <label class="form-check-label text-danger" for="restart_process">
                                <strong>Reiniciar do zero</strong> (ignora progresso salvo)
                            </label>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <button type="submit" id="btn-start" class="btn btn-primary">
                                <i class="fas fa-play me-2"></i>Iniciar Processo
                            </button>
                            <button type="button" id="btn-stop" class="btn btn-danger d-none">
                                <i class="fas fa-stop me-2"></i>Interromper Processo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-terminal me-2"></i>Console de Processamento</h6>
                    <span id="process-status" class="text-muted small">Inativo</span>
                </div>
                <div class="card-body">
                    <div id="import-console">
                        > Aguardando início do processo...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    let abortController = null;

    $(document).ready(function() {
        // Inicializa Select2 com AJAX para busca de CNAEs
        $('#cnaes_select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pesquise por código ou descrição do CNAE',
            ajax: {
                url: '<?= base_url('receita/buscarCnaes') ?>',
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
        const url = new URL('<?= base_url('receita/importar') ?>');
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
            $.get('<?= base_url('receita/parar') ?>');
        }
    }
</script>
<?= $this->endSection() ?>