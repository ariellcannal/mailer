<?= \$this->extend('layouts/main') ?>

<?= \$this->section('content') ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4"><i class="fas fa-at"></i> <?= esc(\$sender['email']) ?></h4>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <h6>Informações</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Email:</th>
                        <td><?= esc(\$sender['email']) ?></td>
                    </tr>
                    <tr>
                        <th>Nome:</th>
                        <td><?= esc(\$sender['name']) ?></td>
                    </tr>
                    <tr>
                        <th>Domínio:</th>
                        <td><?= esc(\$sender['domain']) ?></td>
                    </tr>
                    <tr>
                        <th>Status SES:</th>
                        <td>
                            <?php if (\$sender['ses_verified']): ?>
                                <span class="badge bg-success">Verificado</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Pendente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6">
                <h6>Validação DNS</h6>
                <table class="table table-sm">
                    <tr>
                        <th>SPF:</th>
                        <td>
                            <?php if (\$dnsStatus['spf']['valid']): ?>
                                <i class="fas fa-check-circle text-success"></i> Válido
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i> Inválido
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>DKIM:</th>
                        <td>
                            <?php if (\$dnsStatus['dkim']['valid']): ?>
                                <i class="fas fa-check-circle text-success"></i> Válido
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i> Inválido
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>DMARC:</th>
                        <td>
                            <?php if (\$dnsStatus['dmarc']['valid']): ?>
                                <i class="fas fa-check-circle text-success"></i> Válido
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i> Inválido
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <button class="btn btn-sm btn-outline-primary" onclick="checkDNS()">
                    <i class="fas fa-sync"></i> Verificar Novamente
                </button>
            </div>
        </div>
        
        <?php if (!\$dnsStatus['spf']['valid'] || !\$dnsStatus['dkim']['valid'] || !\$dnsStatus['dmarc']['valid']): ?>
            <div class="alert alert-warning">
                <h6>Instruções de Configuração DNS</h6>
                <?php if (!\$dnsStatus['spf']['valid']): ?>
                    <p><strong>SPF:</strong> <?= \$dnsStatus['spf']['suggestion'] ?></p>
                <?php endif; ?>
                <?php if (!\$dnsStatus['dkim']['valid']): ?>
                    <p><strong>DKIM:</strong> Configure os tokens DKIM fornecidos pela AWS SES</p>
                <?php endif; ?>
                <?php if (!\$dnsStatus['dmarc']['valid']): ?>
                    <p><strong>DMARC:</strong> <?= \$dnsStatus['dmarc']['suggestion'] ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function checkDNS() {
    \$.ajax({
        url: '<?= base_url('senders/check-dns/' . \$sender['id']) ?>',
        method: 'POST',
        success: function(response) {
            if (response.success) {
                alertify.success('DNS verificado!');
                location.reload();
            }
        }
    });
}
</script>
<?= \$this->endSection() ?>
