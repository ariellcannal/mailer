<?= \$this->extend('layouts/main') ?>

<?= \$this->section('content') ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4"><i class="fas fa-file-upload"></i> Importar Contatos</h4>
        
        <div class="alert alert-info">
            <strong>Formato do arquivo:</strong><br>
            - Formatos aceitos: CSV, XLS, XLSX<br>
            - Primeira linha deve conter os cabeçalhos<br>
            - Colunas: <code>email</code> (obrigatório), <code>nome</code> (opcional)
        </div>
        
        <form action="<?= base_url('contacts/import-process') ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Arquivo</label>
                <input type="file" class="form-control" name="file" accept=".csv,.xls,.xlsx" required>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Importar
            </button>
            <a href="<?= base_url('contacts') ?>" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>
<?= \$this->endSection() ?>
