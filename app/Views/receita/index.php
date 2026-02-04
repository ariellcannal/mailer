<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/receita-index.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= $this->include('receita/_toolbar') ?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Configuração de Importação</h6>
                </div>
                <div class="card-body">
                    <form id="form-import">
                        <div class="mb-3">
                            <label class="form-label">Nome da Tarefa (opcional)</label>
                            <input type="text" class="form-control" id="task_name" name="task_name" placeholder="Ex: Importação Janeiro 2025">
                            <small class="text-muted">Identifique esta importação para facilitar o acompanhamento.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Filtrar por CNAEs (opcional)</label>
                            <select id="cnaes_select" name="cnaes[]" class="form-control" multiple></select>
                            <small class="text-muted">Se vazio, importará todos os CNAEs.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Filtrar por Estados (opcional)</label>
                            <select id="ufs_select" name="ufs[]" class="form-control" multiple>
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
                            <small class="text-muted">Se vazio, importará todos os estados.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Criar Lista de Contatos (opcional)</label>
                            <input type="text" class="form-control" id="contact_list_name" name="contact_list_name" placeholder="Ex: Empresas Ativas Janeiro 2025">
                            <small class="text-muted">Se preenchido, uma lista de contatos será criada com os estabelecimentos importados.</small>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="include_contabilidade" name="include_contabilidade" checked>
                            <label class="form-check-label" for="include_contabilidade">
                                Adicionar Contatos de Contabilidade à Nova Lista
                            </label>
                            <small class="form-text text-muted d-block">Se desmarcado, contatos identificados como contabilidade não serão adicionados à lista.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Filtrar por Situação Fiscal</label>
                            <select id="situacoes_select" name="situacoes[]" class="form-control" multiple>
                                <option value="1">1 – NULA</option>
                                <option value="2" selected>2 – ATIVA</option>
                                <option value="3" selected>3 – SUSPENSA</option>
                                <option value="4">4 – INAPTA</option>
                                <option value="8">8 – BAIXADA</option>
                            </select>
                            <small class="text-muted">Padrão: ATIVA, SUSPENSA. Selecione as situações desejadas.</small>
                        </div>
                        
                        <hr>
                        <div class="d-grid gap-2">
                            <button type="submit" id="btn-schedule" class="btn btn-primary">
                                <i class="fas fa-calendar-plus me-2"></i>Agendar Importação
                            </button>
                            <a href="<?= base_url('receita/tasks') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i>Ver Tarefas
                            </a>
                            <a href="<?= base_url('receita/empresas') ?>" class="btn btn-outline-info">
                                <i class="fas fa-building me-2"></i>Consultar Empresas Importadas
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/receita-index.js') ?>" defer></script>
<?= $this->endSection() ?>