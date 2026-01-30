<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h1 class="h3 mb-0">Importações de Contatos</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= site_url('contacts/import') ?>" class="btn btn-primary">
                <i class="fas fa-upload"></i> Nova Importação
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('contacts_success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('contacts_success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('contacts_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('contacts_error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if (empty($imports)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-import fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Nenhuma importação encontrada.</p>
                    <a href="<?= site_url('contacts/import') ?>" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Importar Contatos
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Importados</th>
                                <th class="text-center">Erros</th>
                                <th class="text-center">Progresso</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($imports as $import): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-file-excel text-success me-2"></i>
                                        <?= esc($import['filename']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= number_format($import['total_rows'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">
                                            <?= number_format($import['imported_count'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($import['error_count'] > 0): ?>
                                            <span class="badge bg-danger">
                                                <?= number_format($import['error_count'], 0, ',', '.') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" style="min-width: 150px;">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?= $import['status'] === 'failed' ? 'bg-danger' : 'bg-success' ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= $import['progress_percent'] ?>%"
                                                 aria-valuenow="<?= $import['progress_percent'] ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?= number_format($import['progress_percent'], 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadges = [
                                            'pending' => '<span class="badge bg-secondary">Pendente</span>',
                                            'processing' => '<span class="badge bg-primary">Processando</span>',
                                            'completed' => '<span class="badge bg-success">Concluído</span>',
                                            'failed' => '<span class="badge bg-danger">Falha</span>',
                                        ];
                                        echo $statusBadges[$import['status']] ?? '<span class="badge bg-secondary">' . esc($import['status']) . '</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($import['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pager): ?>
                    <div class="mt-3">
                        <?= $pager->links() ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-refresh a cada 5 segundos se houver importações pendentes ou processando
<?php
$hasPendingOrProcessing = false;
foreach ($imports as $import) {
    if (in_array($import['status'], ['pending', 'processing'])) {
        $hasPendingOrProcessing = true;
        break;
    }
}
?>

<?php if ($hasPendingOrProcessing): ?>
setTimeout(function() {
    location.reload();
}, 5000);
<?php endif; ?>
</script>

<?= $this->endSection() ?>
