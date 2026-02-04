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
                            <label class="form-label">Filtrar por Situação Fiscal</label>
                            <select id="situacoes_select" name="situacoes[]" class="form-control" multiple>
                                <option value="01">01 – NULA</option>
                                <option value="02" selected>02 – ATIVA</option>
                                <option value="03" selected>03 – SUSPENSA</option>
                                <option value="04">04 – INAPTA</option>
                                <option value="08">08 – BAIXADA</option>
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
        
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Sobre o Processamento Assíncrono</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>Como funciona?</h6>
                        <ul class="mb-0">
                            <li>As importações são processadas automaticamente via CRON</li>
                            <li>Apenas uma tarefa é processada por vez, em ordem de criação</li>
                            <li>O progresso é salvo automaticamente a cada 55 segundos</li>
                            <li>Você pode acompanhar o andamento na página "Ver Tarefas"</li>
                            <li>Filtros por CNAE e UF reduzem o tempo de processamento</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Importante</h6>
                        <ul class="mb-0">
                            <li>Certifique-se de que o CRON está configurado para executar a cada minuto</li>
                            <li>Tarefas agendadas serão processadas automaticamente</li>
                            <li>Você pode excluir tarefas "Agendadas" antes do processamento</li>
                            <li>Tarefas "Em Andamento" ou "Concluídas" não podem ser excluídas</li>
                        </ul>
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