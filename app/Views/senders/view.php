<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= base_url('assets/images/icon.svg') ?>" alt="Ícone CANNAL" width="56" height="56">
                <div>
                    <h4 class="section-title mb-1 text-uppercase">Remetente</h4>
                    <span class="text-muted"><?= esc($sender['email']) ?></span>
                </div>
            </div>
            <div>
                <a href="<?= base_url('senders/edit/' . $sender['id']) ?>" class="btn btn-outline-primary btn-sm me-2">
                    <i class="fas fa-pen"></i> Editar
                </a>
                <button class="btn btn-primary btn-sm" onclick="checkDNS()">
                    <i class="fas fa-sync"></i> Verificar DNS
                </button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <h6 class="section-subtitle"><i class="fas fa-id-card text-secondary me-2"></i>Informações</h6>
                <table class="table table-sm table-borderless align-middle">
                    <tr>
                        <th class="w-25 text-uppercase">Email</th>
                        <td><?= esc($sender['email']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-uppercase">Nome</th>
                        <td><?= esc($sender['name']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-uppercase">Domínio</th>
                        <td><?= esc($sender['domain']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-uppercase">Status SES</th>
                        <td>
                            <?php if ((int) $sender['ses_verified'] === 1): ?>
                                <span class="badge bg-success">Verificado</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Pendente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-lg-6">
                <h6 class="section-subtitle"><i class="fas fa-shield-alt text-secondary me-2"></i>Validação DNS</h6>
                <div class="d-flex flex-column gap-3">
                    <div class="status-chip <?= $dnsStatus['spf']['valid'] ? 'status-success' : 'status-danger' ?>">
                        <span>SPF</span>
                        <strong><?= $dnsStatus['spf']['valid'] ? 'Válido' : 'Inválido' ?></strong>
                    </div>
                    <div class="status-chip <?= $dnsStatus['dkim']['valid'] ? 'status-success' : 'status-danger' ?>">
                        <span>DKIM</span>
                        <strong><?= $dnsStatus['dkim']['valid'] ? 'Válido' : 'Inválido' ?></strong>
                    </div>
                    <div class="status-chip <?= $dnsStatus['dmarc']['valid'] ? 'status-success' : 'status-danger' ?>">
                        <span>DMARC</span>
                        <strong><?= $dnsStatus['dmarc']['valid'] ? 'Válido' : 'Inválido' ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$dnsRecords = [];
if (!empty($dnsInstructions['domain_verification'])) {
    $dnsRecords[] = $dnsInstructions['domain_verification'];
}
if (!empty($dnsInstructions['spf'])) {
    $dnsRecords[] = $dnsInstructions['spf'];
}
if (!empty($dnsInstructions['dmarc'])) {
    $dnsRecords[] = $dnsInstructions['dmarc'];
}
if (!empty($dnsInstructions['mx'])) {
    $dnsRecords[] = $dnsInstructions['mx'];
}
if (!empty($dnsInstructions['dkim']) && is_array($dnsInstructions['dkim'])) {
    foreach ($dnsInstructions['dkim'] as $dkimInstruction) {
        $dnsRecords[] = $dkimInstruction;
    }
}
?>

<div class="card">
    <div class="card-body">
        <h5 class="section-title mb-3 text-uppercase"><i class="fas fa-server text-secondary me-2"></i>Registros DNS Requeridos</h5>
        <p class="text-muted">Crie ou atualize os registros abaixo em seu provedor de DNS. Utilize os botões para copiar rapidamente as informações.</p>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase">Tipo</th>
                        <th class="text-uppercase">Chave</th>
                        <th class="text-uppercase">Valor</th>
                        <th class="text-uppercase text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($dnsRecords as $record): ?>
                    <tr>
                        <td><span class="badge bg-primary"><?= esc($record['type'] ?? '-') ?></span></td>
                        <td>
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <span class="text-break"><?= esc($record['name'] ?? '-') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy="<?= esc($record['name'] ?? '-', 'attr') ?>">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <span class="text-break"><?= esc($record['value'] ?? '-') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy="<?= esc($record['value'] ?? '-', 'attr') ?>">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td class="text-center text-muted small"><?= esc($record['description'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function checkDNS() {
    $.ajax({
        url: '<?= base_url('senders/check-dns/' . $sender['id']) ?>',
        method: 'POST',
        success: function(response) {
            if (response.success) {
                alertify.success('DNS verificado!');
                location.reload();
            }
        }
    });
}

document.querySelectorAll('button[data-copy]').forEach(function(button) {
    button.addEventListener('click', function() {
        const value = this.getAttribute('data-copy');

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function() {
                alertify.success('Copiado para a área de transferência');
            }).catch(function() {
                alertify.error('Não foi possível copiar o valor');
            });
            return;
        }

        const tempInput = document.createElement('input');
        tempInput.value = value;
        document.body.appendChild(tempInput);
        tempInput.select();
        try {
            document.execCommand('copy');
            alertify.success('Copiado para a área de transferência');
        } catch (error) {
            alertify.error('Não foi possível copiar o valor');
        }
        document.body.removeChild(tempInput);
    });
});
</script>
<?= $this->endSection() ?>
