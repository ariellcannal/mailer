<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
.step-wizard {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
}
.step {
    flex: 1;
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 0 5px;
    cursor: pointer;
    transition: all 0.3s;
}
.step.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.step.completed {
    background: #28a745;
    color: white;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="card">
    <div class="card-body">
        <h4 class="mb-4"><i class="fas fa-paper-plane"></i> Nova Mensagem</h4>
        
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
                <i class="fas fa-users"></i><br>
                Destinatários
            </div>
            <div class="step" data-step="4">
                <i class="fas fa-redo"></i><br>
                Reenvios
            </div>
            <div class="step" data-step="5">
                <i class="fas fa-check"></i><br>
                Revisar
            </div>
        </div>
        
        <form id="messageForm">
            <!-- Step 1: Informações Básicas -->
            <div class="step-content" data-step="1">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Campanha *</label>
                        <select class="form-select" name="campaign_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($campaigns as $campaign): ?>
                                <option value="<?= $campaign['id'] ?>"><?= esc($campaign['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Remetente *</label>
                        <select class="form-select" name="sender_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($senders as $sender): ?>
                                <option value="<?= $sender['id'] ?>"><?= esc($sender['name']) ?> (<?= esc($sender['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Assunto *</label>
                        <input type="text" class="form-control" name="subject" required placeholder="Digite o assunto do email">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome do Remetente *</label>
                        <input type="text" class="form-control" name="from_name" required placeholder="Ex: Equipe Suporte">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reply-To</label>
                        <input type="email" class="form-control" name="reply_to" placeholder="resposta@seudominio.com">
                    </div>
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            
            <!-- Step 2: Editor GrapesJS -->
            <div class="step-content" data-step="2" style="display:none;">
                <div class="mb-3">
                    <div class="btn-toolbar mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="insertVariable('{{nome}}')">
                            <i class="fas fa-user"></i> Inserir Nome
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="insertVariable('{{email}}')">
                            <i class="fas fa-envelope"></i> Inserir Email
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning me-2" onclick="insertWebviewLink()">
                            <i class="fas fa-external-link-alt"></i> Link Visualização
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="insertOptoutLink()">
                            <i class="fas fa-times-circle"></i> Link Opt-out *
                        </button>
                    </div>
                    <textarea id="messageEditor" name="html_content" class="form-control js-rich-editor" rows="15" required><?= old('html_content') ?></textarea>
                </div>

                <button type="button" class="btn btn-secondary me-2" onclick="prevStep()">
                    <i class="fas fa-arrow-left"></i> Anterior
                </button>
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            
            <!-- Step 3: Destinatários -->
            <div class="step-content" data-step="3" style="display:none;">
                <p>Selecione as listas de contato que receberão esta mensagem:</p>

                <div class="mb-3">
                    <label class="form-label" for="contactListSelect">Listas de contato</label>
                    <select id="contactListSelect" name="contact_lists[]" class="form-select" multiple data-placeholder="Selecione as listas">
                        <?php foreach ($contactLists as $list): ?>
                            <option value="<?= $list['id'] ?>" data-total="<?= $list['total_contacts'] ?? 0 ?>">
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

                <button type="button" class="btn btn-secondary me-2" onclick="prevStep()">
                    <i class="fas fa-arrow-left"></i> Anterior
                </button>
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            
            <!-- Step 4: Reenvios -->
            <div class="step-content" data-step="4" style="display:none;">
                <h5 class="mb-3">Configurar Reenvios Automáticos</h5>
                <p class="text-muted">Configure até 3 reenvios automáticos para contatos que não abriram a mensagem.</p>
                
                <div id="resends-container">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6>Reenvio 1</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Horas após envio</label>
                                    <input type="number" class="form-control" name="resends[0][hours_after]" placeholder="48">
                                    <input type="hidden" name="resends[0][number]" value="1">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Novo assunto</label>
                                    <input type="text" class="form-control" name="resends[0][subject]" placeholder="[LEMBRETE] Assunto original">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6>Reenvio 2</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Horas após envio anterior</label>
                                    <input type="number" class="form-control" name="resends[1][hours_after]" placeholder="72">
                                    <input type="hidden" name="resends[1][number]" value="2">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Novo assunto</label>
                                    <input type="text" class="form-control" name="resends[1][subject]" placeholder="[ÚLTIMA CHANCE] Assunto original">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6>Reenvio 3</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Horas após envio anterior</label>
                                    <input type="number" class="form-control" name="resends[2][hours_after]" placeholder="96">
                                    <input type="hidden" name="resends[2][number]" value="3">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Novo assunto</label>
                                    <input type="text" class="form-control" name="resends[2][subject]" placeholder="[URGENTE] Assunto original">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-secondary me-2" onclick="prevStep()">
                    <i class="fas fa-arrow-left"></i> Anterior
                </button>
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            
            <!-- Step 5: Revisar -->
            <div class="step-content" data-step="5" style="display:none;">
                <h5 class="mb-3">Revisar Mensagem</h5>
                <div id="review-content"></div>
                
                <button type="button" class="btn btn-secondary me-2" onclick="prevStep()">
                    <i class="fas fa-arrow-left"></i> Anterior
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Salvar Mensagem
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?= view('partials/rich_editor_scripts', [
    'editorEngine' => $editorEngine ?? 'tinymce',
    'selector' => '#messageEditor',
    'height' => 600,
]) ?>
<script>
let currentStep = 1;

function nextStep() {
    if (currentStep === 2 && typeof window.syncRichEditors === 'function') {
        window.syncRichEditors();
    }

    const listasSelecionadas = $('#contactListSelect').val() || [];
    const nenhumaListaDisponivel = $('#contactListSelect option').length === 0;

    if (currentStep === 3 && !nenhumaListaDisponivel && listasSelecionadas.length === 0) {
        alertify.error('Selecione pelo menos uma lista de contato.');
        return;
    }

    $('.step-content[data-step="' + currentStep + '"]').hide();
    $('.step[data-step="' + currentStep + '"]').removeClass('active').addClass('completed');

    currentStep++;

    if (currentStep === 5) {
        updateReview();
    }

    $('.step-content[data-step="' + currentStep + '"]').show();
    $('.step[data-step="' + currentStep + '"]').addClass('active');
}

function prevStep() {
    $('.step-content[data-step="' + currentStep + '"]').hide();
    $('.step[data-step="' + currentStep + '"]').removeClass('active');

    currentStep--;

    $('.step-content[data-step="' + currentStep + '"]').show();
    $('.step[data-step="' + currentStep + '"]').removeClass('completed').addClass('active');
}

function insertVariable(variable) {
    if (typeof window.insertRichText === 'function') {
        window.insertRichText(variable);
    }
}

function insertWebviewLink() {
    const html = '<a href="{{webview_link}}" style="color: #999; font-size: 12px;">Clique aqui se não estiver visualizando corretamente</a>';

    if (typeof window.insertRichHtml === 'function') {
        window.insertRichHtml(html);
        alertify.success('Link de visualização inserido!');
    }
}

function insertOptoutLink() {
    const html = '<p style="text-align: center; margin-top: 20px;"><a href="{{optout_link}}" style="color: #666; font-size: 12px;">Descadastrar</a></p>';

    if (typeof window.insertRichHtml === 'function') {
        window.insertRichHtml(html);
        alertify.success('Link de opt-out inserido!');
    }
}

function updateReview() {
    if (typeof window.syncRichEditors === 'function') {
        window.syncRichEditors();
    }

    const htmlContent = $('#messageEditor').val();
    $('#review-content').html(htmlContent);

    const selectedListsText = $('#selectedLists').html();
    const totalRecipients = $('#recipientTotal').text();

    $('#review-content').append(`<div class="alert alert-info mt-3"><strong>Destinatários:</strong> ${selectedListsText}<br><strong>Total estimado:</strong> ${totalRecipients}</div>`);
}

$('#messageForm').on('submit', function(e) {
    e.preventDefault();

    if (typeof window.syncRichEditors === 'function') {
        window.syncRichEditors();
    }

    const formData = $(this).serialize();

    $.ajax({
        url: '<?= base_url('messages/store') ?>',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alertify.success('Mensagem salva com sucesso!');
                setTimeout(() => {
                    window.location.href = '<?= base_url('messages') ?>';
                }, 1500);
            } else {
                alertify.error(response.error || 'Erro ao salvar mensagem');
            }
        },
        error: function() {
            alertify.error('Erro ao salvar mensagem');
        }
    });
});

$(function() {
    $('#contactListSelect').select2({
        width: '100%',
        placeholder: $('#contactListSelect').data('placeholder') || 'Selecione as listas'
    });

    $('#contactListSelect').on('change', function() {
        atualizarDestinatariosPorLista();
    });

    atualizarDestinatariosPorLista();
});

function atualizarDestinatariosPorLista() {
    const listasSelecionadas = $('#contactListSelect').val() || [];

    if (listasSelecionadas.length === 0) {
        $('#selectedLists').text('Nenhuma lista selecionada.');
        $('#recipientTotal').text('0');
        return;
    }

    $.ajax({
        url: '<?= base_url('contact-lists/buscar-contatos') ?>',
        method: 'POST',
        data: { listas: listasSelecionadas },
        success: function(response) {
            if (!response.success) {
                alertify.error(response.error || 'Não foi possível carregar os contatos.');
                return;
            }

            const listaResumo = response.lists
                .map(lista => `<span class="badge bg-secondary me-1 mb-1">${lista.name} (${lista.contacts})</span>`)
                .join(' ');

            $('#selectedLists').html(listaResumo || 'Nenhuma lista selecionada.');
            $('#recipientTotal').text(response.total_contacts || 0);
        },
        error: function() {
            alertify.error('Erro ao buscar contatos das listas.');
        }
    });
}
</script>
<?= $this->endSection() ?>
