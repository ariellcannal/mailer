<?= $this->extend('layouts/main') ?>



<?= $this->section('content') ?>

<div class="card">
    <div class="card-body">
        <?php $isEdit = !empty($message['id'] ?? null); ?>
        <h4 class="mb-4">
            <i class="fas fa-paper-plane"></i>
            <?= $isEdit ? 'Editar Mensagem' : 'Nova Mensagem' ?>
        </h4>
        
        <!-- Step Wizard -->
        <div class="step-wizard">
            <div class="step active" data-step="1">
                <i class="fas fa-info-circle"></i><br>
                Informações Básicas
            </div>
            <div class="step" data-step="2">
                <i class="fas fa-edit"></i><br>
                Conteúdo
            </div>
            <div class="step" data-step="3">
                <i class="fas fa-eye"></i><br>
                Pré-visualização
            </div>
            <div class="step" data-step="4">
                <i class="fas fa-users"></i><br>
                Destinatários
            </div>
            <div class="step" data-step="5">
                <i class="fas fa-paper-plane"></i><br>
                Envios
            </div>
        </div>
        
        <form
            id="messageForm"
            data-store-url="<?= base_url('messages/store') ?>"
            data-index-url="<?= base_url('messages') ?>"
            data-progress-url="<?= base_url('messages/save-progress') ?>"
            data-contacts-url="<?= base_url('contact-lists/buscar-contatos') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="message_id" id="messageId" value="<?= esc($message['id'] ?? '') ?>">
            <!-- Step 1: Informações Básicas -->
            <div class="step-content" data-step="1">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Campanha *</label>
                        <select class="form-control" name="campaign_id" required>
                            <option value="">Selecione...</option>
                            <?php $campaignDefault = old('campaign_id', $message['campaign_id'] ?? ($selectedCampaignId ?? '')); ?>
                            <?php foreach ($campaigns as $campaign): ?>
                                <option value="<?= $campaign['id'] ?>" <?= (string) $campaignDefault === (string) $campaign['id'] ? 'selected' : '' ?>>
                                    <?= esc($campaign['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Remetente *</label>
                        <select class="form-control" name="sender_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($senders as $sender): ?>
                                <option value="<?= $sender['id'] ?>" <?= (string) old('sender_id', $message['sender_id'] ?? '') === (string) $sender['id'] ? 'selected' : '' ?>>
                                    <?= esc($sender['name']) ?> (<?= esc($sender['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Assunto *</label>
                        <input
                            type="text"
                            class="form-control"
                            name="subject"
                            required
                            placeholder="Digite o assunto do email"
                            value="<?= esc(old('subject', $message['subject'] ?? '')) ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome do Remetente *</label>
                        <input
                            type="text"
                            class="form-control"
                            name="from_name"
                            required
                            placeholder="Ex: Equipe Suporte"
                            value="<?= esc(old('from_name', $message['from_name'] ?? '')) ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reply-To</label>
                        <input
                            type="email"
                            class="form-control"
                            name="reply_to"
                            placeholder="resposta@seudominio.com"
                            value="<?= esc(old('reply_to', $message['reply_to'] ?? '')) ?>">
                    </div>
                </div>
                
                <button type="button" class="btn btn-primary nextStep">
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            
            <!-- Step 2: Editor GrapesJS -->
            <div class="step-content" data-step="2" style="display:none;">
                <?= view('partials/rich_editor', [
                    'height' => 400,
                    'htmlContent' => old('html_content', $message['html_content'] ?? ''),
                ]) ?>
                <div class="d-flex justify-content-between mt-3">
                    <button type="button" class="btn btn-secondary me-2 prevStep">
                    <i class="fas fa-arrow-left"></i> Anterior
                </button>
                <button type="button" class="btn btn-primary nextStep">
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
                </div>
            </div>

            <!-- Step 3: Pré-visualização -->
            <div class="step-content" data-step="3" style="display:none;">
                <h5 class="mb-3">Pré-visualização do conteúdo</h5>
                <div id="previewPane" class="border rounded p-3 bg-light" aria-live="polite"></div>

                <div class="mt-3">
                    <button type="button" class="btn btn-secondary me-2 prevStep">
                        <i class="fas fa-arrow-left"></i> Anterior
                    </button>
                    <button type="button" class="btn btn-primary nextStep">
                        Próximo <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 4: Destinatários -->
            <div class="step-content" data-step="4" style="display:none;">
                <p>Selecione as listas de contato que receberão esta mensagem:</p>

                <div class="mb-3">
                    <label class="form-label" for="contactListSelect">Listas de contato</label>
                    <select id="contactListSelect" name="contact_lists[]" class="form-select" multiple data-placeholder="Selecione as listas">
                        <?php $preselectedLists = $selectedLists ?? []; ?>
                        <?php foreach ($contactLists as $list): ?>
                            <option
                                value="<?= $list['id'] ?>"
                                data-total="<?= $list['total_contacts'] ?? 0 ?>"
                                <?= in_array((int) $list['id'], array_map('intval', $preselectedLists), true) ? 'selected' : '' ?>>
                                <?= esc($list['name']) ?> (<?= (int) ($list['total_contacts'] ?? 0) ?> contatos)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($contactLists)): ?>
                        <div class="alert alert-warning mt-2" role="status">
                            Nenhuma lista de contato encontrada. Crie uma lista antes de continuar.
                        </div>
                    <?php endif; ?>
                    <small class="text-muted">É possível combinar múltiplas listas; os contatos duplicados serão removidos automaticamente.</small>
                </div>

                <div id="recipientSummary" class="p-3 border rounded bg-light" aria-live="polite">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-list-check text-primary me-2"></i>
                        <strong>Listas selecionadas</strong>
                    </div>
                    <div id="selectedLists" class="mb-2 text-muted">Nenhuma lista selecionada.</div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-users text-success me-2"></i>
                        <span>Total estimado de destinatários: <strong id="recipientTotal">0</strong></span>
                    </div>
                </div>

                <button type="button" class="btn btn-secondary me-2 prevStep">
                    <i class="fas fa-arrow-left"></i> Anterior
                </button>
                <button type="button" class="btn btn-primary nextStep">
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>

            <!-- Step 5: Envios (Agendamento + Reenvios) -->
            <div class="step-content" data-step="5" style="display:none;">
                <h5 class="mb-4">Configurar Envios</h5>
                
                <!-- Bloco: Primeiro Envio -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-paper-plane"></i> Primeiro Envio</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="scheduled_at">Data e hora do envio *</label>
                                <input
                                    type="text"
                                    id="scheduled_at"
                                    name="scheduled_at"
                                    class="form-control"
                                    value="<?= esc(old('scheduled_at', isset($message['scheduled_at']) ? date('d/m/Y H:i', strtotime($message['scheduled_at'])) : date('d/m/Y H:i', strtotime('+10 minutes')))) ?>"
                                    placeholder="dd/mm/aaaa hh:mm"
                                    required>
                                <small class="text-muted">A aplicação iniciará o disparo automaticamente neste horário.</small>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="alert alert-info mb-0 w-100" id="scheduleSummary" aria-live="polite">
                                    Defina a data e hora para iniciar o envio.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bloco: Reenvios -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-redo"></i> Reenvios</h6>
                        <button type="button" class="btn btn-sm btn-light" id="btnAddResend">
                            <i class="fas fa-plus"></i> Adicionar Reenvio
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Configure até 3 reenvios automáticos para contatos que não abriram a mensagem.</p>
                        
                        <table class="table table-bordered" id="resendsTable">
                            <thead>
                                <tr>
                                    <th width="15%">Reenvio</th>
                                    <th width="25%">Data/Hora</th>
                                    <th width="50%">Assunto</th>
                                    <th width="10%" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Preenchido via JavaScript -->
                            </tbody>
                        </table>
                        
                        <!-- Hidden inputs para enviar dados -->
                        <div id="resendHiddenInputs"></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary prevStep">
                        <i class="fas fa-arrow-left"></i> Anterior
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirmar Agendamento
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Adicionar/Editar Reenvio -->
<div class="modal fade" id="resendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resendModalLabel">Adicionar Reenvio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="resendForm">
                    <input type="hidden" id="resendIndex">
                    
                    <div class="mb-3">
                        <label for="resendNumber" class="form-label">Número do Reenvio</label>
                        <select class="form-select" id="resendNumber" required>
                            <option value="">Selecione...</option>
                            <option value="1">Reenvio 1</option>
                            <option value="2">Reenvio 2</option>
                            <option value="3">Reenvio 3</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resendScheduledAt" class="form-label">Data/Hora do Reenvio</label>
                        <input
                            type="text"
                            class="form-control"
                            id="resendScheduledAt"
                            placeholder="dd/mm/aaaa hh:mm"
                            required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resendSubject" class="form-label">Assunto</label>
                        <input
                            type="text"
                            class="form-control"
                            id="resendSubject"
                            placeholder="Assunto da mensagem"
                            required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSaveResend">Salvar</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (isset($editPermissions)): ?>
<script type="application/json" id="edit-permissions-data"><?= json_encode($editPermissions) ?></script>
<?php endif; ?>
<?php if (isset($resendRules) && !empty($resendRules)): ?>
<script type="application/json" id="resend-rules-data"><?= json_encode($resendRules) ?></script>
<?php endif; ?>
<script src="<?= base_url('assets/js/messages-detail.js') ?>" defer></script>
<?= $this->endSection() ?>
