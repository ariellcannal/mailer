<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-building me-2"></i>Detalhes da Empresa</h4>
        <a href="<?= base_url('receita/empresas') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
    </div>
    
    <?php 
    $cnpj = sprintf('%s/%s-%s', $estabelecimento['cnpj_basico'], $estabelecimento['cnpj_ordem'], $estabelecimento['cnpj_dv']);
    ?>
    
    <!-- Dados do Estabelecimento -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-store me-2"></i>Dados do Estabelecimento</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>CNPJ:</strong>
                    <p class="mb-0"><code class="fs-5"><?= $cnpj ?></code></p>
                </div>
                <div class="col-md-6">
                    <strong>Matriz/Filial:</strong>
                    <p class="mb-0"><?= $estabelecimento['identificador_matriz_filial'] == '1' ? 'Matriz' : 'Filial' ?></p>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Nome Fantasia:</strong>
                    <p class="mb-0"><?= $estabelecimento['nome_fantasia'] ?: '-' ?></p>
                </div>
                <div class="col-md-6">
                    <strong>Razão Social:</strong>
                    <p class="mb-0"><?= $empresa['razao_social'] ?? '-' ?></p>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Situação Cadastral:</strong>
                    <p class="mb-0">
                        <?php
                        $situacoes = [
                            '01' => 'NULA',
                            '02' => 'ATIVA',
                            '03' => 'SUSPENSA',
                            '04' => 'INAPTA',
                            '08' => 'BAIXADA'
                        ];
                        $situacao = $situacoes[$estabelecimento['situacao_cadastral']] ?? 'Desconhecida';
                        $badgeClass = $estabelecimento['situacao_cadastral'] == '02' ? 'success' : 'secondary';
                        ?>
                        <span class="badge bg-<?= $badgeClass ?>"><?= $situacao ?></span>
                    </p>
                </div>
                <div class="col-md-4">
                    <strong>Data Situação Cadastral:</strong>
                    <p class="mb-0"><?= $estabelecimento['data_situacao_cadastral'] ?: '-' ?></p>
                </div>
                <div class="col-md-4">
                    <strong>Data de Início:</strong>
                    <p class="mb-0"><?= $estabelecimento['data_inicio_atividade'] ?: '-' ?></p>
                </div>
            </div>
            
            <hr>
            
            <h6 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>Endereço</h6>
            <div class="row mb-3">
                <div class="col-md-8">
                    <strong>Logradouro:</strong>
                    <p class="mb-0">
                        <?= $estabelecimento['tipo_logradouro'] ?> 
                        <?= $estabelecimento['logradouro'] ?>, 
                        <?= $estabelecimento['numero'] ?> 
                        <?= $estabelecimento['complemento'] ? '- ' . $estabelecimento['complemento'] : '' ?>
                    </p>
                </div>
                <div class="col-md-4">
                    <strong>Bairro:</strong>
                    <p class="mb-0"><?= $estabelecimento['bairro'] ?: '-' ?></p>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Município:</strong>
                    <p class="mb-0"><?= $estabelecimento['municipio'] ?: '-' ?></p>
                </div>
                <div class="col-md-2">
                    <strong>UF:</strong>
                    <p class="mb-0"><?= $estabelecimento['uf'] ?></p>
                </div>
                <div class="col-md-3">
                    <strong>CEP:</strong>
                    <p class="mb-0"><?= $estabelecimento['cep'] ?: '-' ?></p>
                </div>
            </div>
            
            <hr>
            
            <h6 class="mb-3"><i class="fas fa-phone me-2"></i>Contato</h6>
            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Telefone 1:</strong>
                    <p class="mb-0">
                        <?php if ($estabelecimento['ddd_telefone_1'] && $estabelecimento['telefone_1']): ?>
                            (<?= $estabelecimento['ddd_telefone_1'] ?>) <?= $estabelecimento['telefone_1'] ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4">
                    <strong>Telefone 2:</strong>
                    <p class="mb-0">
                        <?php if ($estabelecimento['ddd_telefone_2'] && $estabelecimento['telefone_2']): ?>
                            (<?= $estabelecimento['ddd_telefone_2'] ?>) <?= $estabelecimento['telefone_2'] ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4">
                    <strong>Fax:</strong>
                    <p class="mb-0">
                        <?php if ($estabelecimento['ddd_fax'] && $estabelecimento['fax']): ?>
                            (<?= $estabelecimento['ddd_fax'] ?>) <?= $estabelecimento['fax'] ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <strong>E-mail:</strong>
                    <p class="mb-0"><?= $estabelecimento['correio_eletronico'] ?: '-' ?></p>
                </div>
            </div>
            
            <hr>
            
            <h6 class="mb-3"><i class="fas fa-briefcase me-2"></i>Atividade Econômica</h6>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>CNAE Fiscal Principal:</strong>
                    <p class="mb-0"><code><?= $estabelecimento['cnae_fiscal_principal'] ?></code></p>
                </div>
                <div class="col-md-6">
                    <strong>CNAE Fiscal Secundária:</strong>
                    <p class="mb-0"><?= $estabelecimento['cnae_fiscal_secundaria'] ?: '-' ?></p>
                </div>
            </div>
            
            <?php if (!empty($empresa)): ?>
            <hr>
            
            <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Dados da Empresa</h6>
            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Natureza Jurídica:</strong>
                    <p class="mb-0"><?= $empresa['natureza_juridica'] ?? '-' ?></p>
                </div>
                <div class="col-md-4">
                    <strong>Porte:</strong>
                    <p class="mb-0">
                        <?php
                        $portes = [
                            '00' => 'Não informado',
                            '01' => 'Micro empresa',
                            '03' => 'Empresa de pequeno porte',
                            '05' => 'Demais'
                        ];
                        echo $portes[$empresa['porte'] ?? '00'] ?? 'Desconhecido';
                        ?>
                    </p>
                </div>
                <div class="col-md-4">
                    <strong>Capital Social:</strong>
                    <p class="mb-0">R$ <?= number_format($empresa['capital_social'] ?? 0, 2, ',', '.') ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sócios -->
    <?php if (!empty($socios)): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Sócios (<?= count($socios) ?>)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Identificador</th>
                            <th>Nome/Razão Social</th>
                            <th>CPF/CNPJ</th>
                            <th>Qualificação</th>
                            <th>Data Entrada</th>
                            <th>Faixa Etária</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($socios as $socio): ?>
                        <tr>
                            <td><?= $socio['identificador_de_socio'] ?></td>
                            <td><?= $socio['nome_socio_razao_social'] ?></td>
                            <td><code><?= $socio['cpf_cnpj_socio'] ?: '-' ?></code></td>
                            <td><?= $socio['qualificacao_socio'] ?></td>
                            <td><?= $socio['data_entrada_sociedade'] ?: '-' ?></td>
                            <td><?= $socio['faixa_etaria'] ?: '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Nenhum sócio cadastrado para esta empresa.
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
