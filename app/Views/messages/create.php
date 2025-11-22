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

.editor-panel {
    border: 1px solid #e9ecef;
}

.editor-panel .card-body {
    background: #fff;
}

.editor-fullscreen {
    position: fixed;
    inset: 0;
    z-index: 1080;
    background: #fff;
    padding: 1.5rem;
    overflow-y: auto;
}

.editor-fullscreen .editor-panel {
    height: calc(100vh - 180px);
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
                <i class="fas fa-check"></i><br>
                Agendamento
            </div>
            <div class="step" data-step="5">
                <i class="fas fa-redo"></i><br>
                Reenvios
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
                <div class="row g-3" id="editorWrapper" aria-live="polite">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div class="btn-group" role="group" aria-label="Alternar modo do editor">
                                <button type="button" class="btn btn-outline-primary active" id="editorModeCreate" onclick="switchEditorMode('create')">
                                    <i class="fas fa-pen"></i> Criar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="editorModePreview" onclick="switchEditorMode('preview')">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                            </div>

                            <div class="btn-group" role="group" aria-label="Ações do editor">
                                <button type="button" class="btn btn-outline-dark" id="editorFullscreenToggle" onclick="toggleEditorFullscreen()">
                                    <i class="fas fa-expand"></i> Tela cheia
                                </button>
                            </div>
                        </div>

                        <div id="editorCreatePanel" class="editor-panel card shadow-sm">
                            <div class="card-body">
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
                        </div>

                        <div id="editorPreviewPanel" class="editor-panel card shadow-sm d-none">
                            <div class="card-body bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Preview</h6>
                                    <span class="badge bg-secondary">Leitura somente</span>
                                </div>
                                <div id="editorPreviewContent" class="border rounded p-3 bg-white" style="min-height: 400px;"></div>
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
            
            <!-- Step 4: Agendamento -->
            <div class="step-content" data-step="4" style="display:none;">
                <h5 class="mb-3">Agendamento</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="scheduledAt">Primeiro envio *</label>
                        <input type="datetime-local" id="scheduledAt" name="scheduled_at" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                        <small class="text-muted">A aplicação iniciará o disparo automaticamente neste horário.</small>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="alert alert-info mb-0 w-100" id="scheduleSummary" aria-live="polite">
                            Defina a data e hora para iniciar o envio.
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

            <!-- Step 5: Reenvios -->
            <div class="step-content" data-step="5" style="display:none;">
                <h5 class="mb-3">Configurar Reenvios Automáticos</h5>
                <p class="text-muted">Configure até 3 reenvios automáticos para contatos que não abriram a mensagem.</p>

                <div id="resends-container">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6>Reenvio 1</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Agendar em</label>
                                    <input type="datetime-local" class="form-control" name="resends[0][scheduled_at]">
                                    <input type="hidden" name="resends[0][number]" value="1">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Novo assunto</label>
                                    <input type="text" class="form-control" name="resends[0][subject]" placeholder="Assunto da mensagem" value="<?= esc($message['subject'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6>Reenvio 2</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Agendar em</label>
                                    <input type="datetime-local" class="form-control" name="resends[1][scheduled_at]">
                                    <input type="hidden" name="resends[1][number]" value="2">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Novo assunto</label>
                                    <input type="text" class="form-control" name="resends[1][subject]" placeholder="Assunto da mensagem" value="<?= esc($message['subject'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6>Reenvio 3</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Agendar em</label>
                                    <input type="datetime-local" class="form-control" name="resends[2][scheduled_at]">
                                    <input type="hidden" name="resends[2][number]" value="3">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Novo assunto</label>
                                    <input type="text" class="form-control" name="resends[2][subject]" placeholder="Assunto da mensagem" value="<?= esc($message['subject'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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

    if (currentStep === 4) {
        updateScheduleSummary();
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

function switchEditorMode(mode) {
    if (mode !== 'create' && mode !== 'preview') {
        return;
    }

    if (mode === 'preview') {
        renderEditorPreview();
    }

    document.getElementById('editorModeCreate').classList.toggle('active', mode === 'create');
    document.getElementById('editorModePreview').classList.toggle('active', mode === 'preview');

    document.getElementById('editorCreatePanel').classList.toggle('d-none', mode !== 'create');
    document.getElementById('editorPreviewPanel').classList.toggle('d-none', mode !== 'preview');
}

function renderEditorPreview() {
    if (typeof window.syncRichEditors === 'function') {
        window.syncRichEditors();
    }

    const previewElement = document.getElementById('editorPreviewContent');
    if (!previewElement) {
        return;
    }

    let fallbackContent = '';
    const messageElement = document.getElementById('messageEditor');
    if (messageElement) {
        fallbackContent = messageElement.value;
    }

    const content = typeof window.getRichEditorData === 'function'
        ? window.getRichEditorData()
        : fallbackContent;

    previewElement.innerHTML = content || '<p class="text-muted">Nenhum conteúdo para pré-visualizar.</p>';
}

function toggleEditorFullscreen() {
    const wrapper = document.getElementById('editorWrapper');
    const toggleButton = document.getElementById('editorFullscreenToggle');

    if (!wrapper || !toggleButton) {
        return;
    }

    wrapper.classList.toggle('editor-fullscreen');

    const icon = toggleButton.querySelector('i');
    if (icon) {
        icon.classList.toggle('fa-expand');
        icon.classList.toggle('fa-compress');
    }

    toggleButton.classList.toggle('active');
}

function updateScheduleSummary() {
    const selectedListsText = $('#selectedLists').text() || 'Nenhuma lista selecionada.';
    const totalRecipients = $('#recipientTotal').text();
    const scheduledAt = $('#scheduledAt').val();

    const formattedSchedule = (() => {
        if (!scheduledAt) {
            return 'não definido';
        }

        const date = new Date(scheduledAt);

        if (Number.isNaN(date.getTime())) {
            return 'não definido';
        }

        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = String(date.getFullYear()).slice(-2);
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return `${day}/${month}/${year} ${hours}:${minutes}`;
    })();

    $('#scheduleSummary').html(`<strong>Resumo:</strong> ${selectedListsText}<br>Total estimado: <strong>${totalRecipients}</strong><br>Envio inicial: <strong>${formattedSchedule}</strong>`);
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
        updateScheduleSummary();
    });

    $('#scheduledAt').on('change', function() {
        updateScheduleSummary();
    });

    atualizarDestinatariosPorLista();
    updateScheduleSummary();
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
