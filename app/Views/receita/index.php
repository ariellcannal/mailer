<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/receita-index.css') ?>">
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
<script src="<?= base_url('assets/js/receita-index.js') ?>" defer></script>
<?= $this->endSection() ?>