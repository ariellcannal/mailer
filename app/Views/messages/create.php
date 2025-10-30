<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
<link rel="stylesheet" href="https://unpkg.com/grapesjs-preset-newsletter/dist/grapesjs-preset-newsletter.css">
<style>
.gjs-editor {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}
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
                    
                    <div id="gjs" style="height: 600px;"></div>
                    <input type="hidden" name="html_content" id="html_content">
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
                <p>Selecione os contatos que receberão esta mensagem:</p>
                <!-- Implementar seleção de contatos/listas -->
                <div class="alert alert-info">
                    Funcionalidade de seleção de contatos será implementada aqui.
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
<script src="https://unpkg.com/grapesjs"></script>
<script src="https://unpkg.com/grapesjs-preset-newsletter"></script>

<script>
let currentStep = 1;
let editor;

$(document).ready(function() {
    initGrapesJS();
});

function initGrapesJS() {
    editor = grapesjs.init({
        container: '#gjs',
        plugins: ['gjs-preset-newsletter'],
        pluginsOpts: {
            'gjs-preset-newsletter': {}
        },
        storageManager: false,
        assetManager: {
            upload: false,
        },
    });
}

function nextStep() {
    if (currentStep === 2) {
        // Salvar HTML do editor
        const html = editor.getHtml();
        const css = editor.getCss();
        const fullHtml = `<style>${css}</style>${html}`;
        $('#html_content').val(fullHtml);
    }
    
    $('.step-content[data-step="' + currentStep + '"]').hide();
    $('.step[data-step="' + currentStep + '"]').removeClass('active').addClass('completed');
    
    currentStep++;
    
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
    editor.getSelected().append(`<span>${variable}</span>`);
}

function insertWebviewLink() {
    const html = '<a href="{{webview_link}}" style="color: #999; font-size: 12px;">Clique aqui se não estiver visualizando corretamente</a>';
    editor.getSelected().append(html);
    alertify.success('Link de visualização inserido!');
}

function insertOptoutLink() {
    const html = '<p style="text-align: center; margin-top: 20px;"><a href="{{optout_link}}" style="color: #666; font-size: 12px;">Descadastrar</a></p>';
    editor.getSelected().append(html);
    alertify.success('Link de opt-out inserido!');
}

$('#messageForm').on('submit', function(e) {
    e.preventDefault();
    
    // Salvar HTML final
    const html = editor.getHtml();
    const css = editor.getCss();
    const fullHtml = `<style>${css}</style>${html}`;
    $('#html_content').val(fullHtml);
    
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
</script>
<?= $this->endSection() ?>
