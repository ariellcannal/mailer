<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/receita-empresas.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= $this->include('receita/_toolbar') ?>
    
    <h4 class="mb-4"><i class="fas fa-building me-2"></i>Empresas Importadas</h4>
    
    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="form-filtros">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Nome / Razão Social</label>
                        <input type="text" class="form-control" id="filtro_nome" name="nome" placeholder="Digite o nome">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">CNPJ Base</label>
                        <input type="text" class="form-control" id="filtro_cnpj" name="cnpj_basico" placeholder="00000000" maxlength="8">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">CNAE</label>
                        <select id="filtro_cnae" name="cnae[]" class="form-control" multiple></select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Estado (UF)</label>
                        <select id="filtro_uf" name="uf" class="form-control">
                            <option value="">Todos</option>
                            <option value="AC">Acre</option>
                            <option value="AL">Alagoas</option>
                            <option value="AP">Amapá</option>
                            <option value="AM">Amazonas</option>
                            <option value="BA">Bahia</option>
                            <option value="CE">Ceará</option>
                            <option value="DF">Distrito Federal</option>
                            <option value="ES">Espírito Santo</option>
                            <option value="GO">Goiás</option>
                            <option value="MA">Maranhão</option>
                            <option value="MT">Mato Grosso</option>
                            <option value="MS">Mato Grosso do Sul</option>
                            <option value="MG">Minas Gerais</option>
                            <option value="PA">Pará</option>
                            <option value="PB">Paraíba</option>
                            <option value="PR">Paraná</option>
                            <option value="PE">Pernambuco</option>
                            <option value="PI">Piauí</option>
                            <option value="RJ">Rio de Janeiro</option>
                            <option value="RN">Rio Grande do Norte</option>
                            <option value="RS">Rio Grande do Sul</option>
                            <option value="RO">Rondônia</option>
                            <option value="RR">Roraima</option>
                            <option value="SC">Santa Catarina</option>
                            <option value="SP">São Paulo</option>
                            <option value="SE">Sergipe</option>
                            <option value="TO">Tocantins</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Filtros Adicionais</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="filtro_com_email" name="com_email" value="1">
                            <label class="form-check-label" for="filtro_com_email">
                                Somente com e-mail
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="filtro_com_telefone" name="com_telefone" value="1">
                            <label class="form-check-label" for="filtro_com_telefone">
                                Somente com telefone
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Card de Adição à Lista (aparece quando filtros estão ativos) -->
    <div id="card-add-to-list" class="card shadow-sm mb-4" style="display: none;">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-auto">
                    <p class="mb-0">
                        <strong id="total-empresas-encontradas">0</strong> empresas encontradas.
                        <span class="text-muted">Adicionar à lista de contatos:</span>
                    </p>
                </div>
                <div class="col">
                    <select id="select-contact-lists" name="contact_lists[]" class="form-control" multiple>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" id="btn-add-to-list" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Adicionar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabela de Resultados -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div id="loading" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2 text-muted">Buscando empresas...</p>
            </div>
            
            <div id="resultados">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>CNPJ</th>
                                <th>Nome Fantasia</th>
                                <th>Telefone 1</th>
                                <th>Telefone 2</th>
                                <th>Fax</th>
                                <th>E-mail</th>
                                <th width="100">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-empresas">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <p>Use os filtros acima para buscar empresas</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <div id="paginacao" class="d-flex justify-content-between align-items-center mt-3" style="display: none !important;">
                    <div id="info-paginacao" class="text-muted"></div>
                    <nav>
                        <ul class="pagination mb-0" id="pagination-links"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/receita-empresas.js') ?>"></script>
<?= $this->endSection() ?>
