<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4"><i class="fas fa-edit"></i> Editar Mensagem</h4>

        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= esc(session('error')) ?></div>
        <?php endif; ?>

        <form action="<?= base_url('messages/update/' . $message['id']) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Campanha</label>
                    <select class="form-select" name="campaign_id">
                        <option value="">Selecione...</option>
                        <?php foreach ($campaigns as $campaign): ?>
                            <option value="<?= $campaign['id'] ?>" <?= (int) $message['campaign_id'] === (int) $campaign['id'] ? 'selected' : '' ?>>
                                <?= esc($campaign['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Remetente</label>
                    <select class="form-select" name="sender_id" required>
                        <?php foreach ($senders as $sender): ?>
                            <option value="<?= $sender['id'] ?>" <?= (int) $message['sender_id'] === (int) $sender['id'] ? 'selected' : '' ?>>
                                <?= esc($sender['name']) ?> (<?= esc($sender['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Assunto</label>
                    <input type="text" class="form-control" name="subject" value="<?= esc($message['subject']) ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome do Remetente</label>
                    <input type="text" class="form-control" name="from_name" value="<?= esc($message['from_name']) ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Reply-To</label>
                    <input type="email" class="form-control" name="reply_to" value="<?= esc($message['reply_to']) ?>">
                </div>

                <div class="col-12 mb-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2 gap-2">
                        <label class="form-label mb-0">HTML</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="openTemplateModal">
                                <i class="fas fa-file-import"></i> Importar template
                            </button>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-image"></i> Adicionar imagem
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><button class="dropdown-item" type="button" id="uploadFromComputer"><i class="fas fa-upload"></i> Upload do computador</button></li>
                                    <li><button class="dropdown-item" type="button" id="insertFromUrl"><i class="fas fa-link"></i> Inserir via URL</button></li>
                                    <li><button class="dropdown-item" type="button" id="insertFromManager"><i class="fas fa-folder-open"></i> Inserir via File Manager</button></li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-outline-info btn-sm" id="openFileManager">
                                <i class="fas fa-folder"></i> File Manager
                            </button>
                        </div>
                    </div>
                    <textarea name="html_content" class="form-control js-rich-editor" rows="12" required><?= old('html_content', $message['html_content']) ?></textarea>
                </div>
            </div>

            <hr>
            <h5 class="mb-3">Destinatários</h5>
            <div class="row mb-4">
                <div class="col-md-8">
                    <label class="form-label" for="contactListSelect">Listas de contato</label>
                    <select
                        id="contactListSelect"
                        name="contact_lists[]"
                        class="form-select"
                        multiple
                        data-placeholder="Selecione as listas"
                        <?= $canEditRecipients ? '' : 'disabled' ?>
                    >
                        <?php foreach ($contactLists as $list): ?>
                            <option
                                value="<?= $list['id'] ?>"
                                data-total="<?= $list['total_contacts'] ?? 0 ?>"
                                <?= in_array((int) $list['id'], $selectedLists, true) ? 'selected' : '' ?>
                            >
                                <?= esc($list['name']) ?> <?= isset($list['total_contacts']) ? '(' . (int) $list['total_contacts'] . ' contatos)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$canEditRecipients): ?>
                        <small class="text-muted">Altere as listas apenas se o primeiro envio ainda não tiver sido processado.</small>
                    <?php else: ?>
                        <small class="text-muted">Se nenhuma lista for alterada, os destinatários atuais serão mantidos.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-light h-100">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-users text-primary me-2"></i>
                            <div>
                                <div class="fw-bold">Destinatários atuais</div>
                                <div class="text-muted">Total: <?= (int) $currentRecipients ?></div>
                            </div>
                        </div>
                        <?php if (!empty($recipientBreakdown)): ?>
                            <ul class="list-unstyled mb-0 small">
                                <?php foreach ($recipientBreakdown as $breakdown): ?>
                                    <li>
                                        <i class="fas fa-list-check text-secondary me-1"></i>
                                        <?= esc($breakdown['name']) ?> — <?= (int) $breakdown['recipients'] ?> contatos
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted small mb-0">Nenhuma lista detectada para os destinatários atuais.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <hr>
            <h5 class="mb-3">Agendamento</h5>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="scheduledAt">Primeiro envio</label>
                    <input
                        type="datetime-local"
                        id="scheduledAt"
                        name="scheduled_at"
                        class="form-control"
                        value="<?= $message['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($message['scheduled_at'])) : '' ?>"
                        <?= $canReschedule ? '' : 'disabled' ?>
                    >
                    <small class="text-muted">Disponível para reagendamento enquanto o primeiro envio não foi processado.</small>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="alert <?= $canReschedule ? 'alert-info' : 'alert-secondary' ?> mb-0 w-100" role="status">
                        <?php if ($message['scheduled_at']): ?>
                            Agendado para <?= date('d/m/Y H:i', strtotime($message['scheduled_at'])) ?> (status: <?= esc($message['status']) ?>).
                        <?php else: ?>
                            Nenhum agendamento registrado.
                        <?php endif; ?>
                        <?php if (!$canReschedule && $message['scheduled_at']): ?>
                            <br><strong>Reagendamento indisponível</strong>: permitido apenas antes do primeiro envio ser processado.
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($resendRules)): ?>
                <div class="mb-4">
                    <h6 class="mb-2">Reenvios</h6>
                    <div class="row g-3">
                        <?php foreach ($resendRules as $rule): ?>
                            <?php $isEditable = ($rule['status'] === 'pending' && empty($resendLocks[$rule['id']] ?? false)); ?>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-primary">Reenvio #<?= (int) $rule['resend_number'] ?></span>
                                            <span class="badge <?= $rule['status'] === 'pending' ? 'bg-info text-dark' : 'bg-secondary' ?>">Status: <?= esc($rule['status']) ?></span>
                                        </div>
                                        <p class="mb-2">Assunto: <strong><?= esc($rule['subject_override'] ?: $message['subject']) ?></strong></p>
                                        <p class="mb-3">Programado para <?= date('d/m/Y H:i', strtotime($rule['scheduled_at'])) ?></p>
                                        <div class="mb-2">
                                            <label class="form-label" for="resend-<?= $rule['id'] ?>">Novo agendamento</label>
                                            <input
                                                type="datetime-local"
                                                class="form-control"
                                                id="resend-<?= $rule['id'] ?>"
                                                name="resends[<?= $rule['id'] ?>][scheduled_at]"
                                                value="<?= $rule['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($rule['scheduled_at'])) : '' ?>"
                                                <?= $isEditable ? '' : 'disabled' ?>
                                            >
                                            <small class="text-muted">Somente reenvios pendentes ainda não enfileirados podem ser alterados.</small>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label" for="resend-subject-<?= $rule['id'] ?>">Assunto do reenvio</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="resend-subject-<?= $rule['id'] ?>"
                                                name="resends[<?= $rule['id'] ?>][subject]"
                                                value="<?= esc($rule['subject_override'] ?: $message['subject']) ?>"
                                                <?= $isEditable ? '' : 'disabled' ?>
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Atualizar
                </button>
                <a href="<?= base_url('messages/view/' . $message['id']) ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalLabel">Importar template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="templateSearch" class="form-label">Buscar template</label>
                    <input type="text" class="form-control" id="templateSearch" placeholder="Filtrar por nome ou descrição">
                </div>
                <div id="templateFeedback" class="alert alert-info d-none" role="status"></div>
                <div class="row g-3" id="templateList"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmTemplateImport" disabled>Inserir no conteúdo</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imageUrlModal" tabindex="-1" aria-labelledby="imageUrlModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageUrlModalLabel">Inserir imagem via URL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="imageUrlInput" class="form-label">URL da imagem</label>
                    <input type="url" class="form-control" id="imageUrlInput" placeholder="https://exemplo.com/imagem.jpg">
                </div>
                <div class="mb-0">
                    <label for="imageAltInput" class="form-label">Texto alternativo</label>
                    <input type="text" class="form-control" id="imageAltInput" placeholder="Descrição da imagem">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="insertImageUrl">Inserir imagem</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="fileManagerModal" tabindex="-1" aria-labelledby="fileManagerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fileManagerModalLabel">Biblioteca de arquivos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <input type="file" class="form-control" id="fileUploadInput" accept="image/*" hidden>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="triggerFileUpload">
                        <i class="fas fa-upload"></i> Enviar novo arquivo
                    </button>
                    <div class="flex-grow-1"></div>
                    <div id="fileManagerStatus" class="text-muted small"></div>
                </div>
                <div class="row g-3" id="fileManagerList"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="insertSelectedFile" disabled>Inserir no conteúdo</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/rich_editor_scripts', [
    'editorEngine' => $editorEngine ?? 'tinymce',
    'selector' => 'textarea[name="html_content"]',
    'height' => 600,
]) ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const templateModal = new bootstrap.Modal('#templateModal');
        const fileManagerModal = new bootstrap.Modal('#fileManagerModal');
        const imageUrlModal = new bootstrap.Modal('#imageUrlModal');

        const templateSearchInput = document.getElementById('templateSearch');
        const templateList = document.getElementById('templateList');
        const templateFeedback = document.getElementById('templateFeedback');
        const confirmTemplateImport = document.getElementById('confirmTemplateImport');
        let selectedTemplateContent = '';

        const fileManagerList = document.getElementById('fileManagerList');
        const fileManagerStatus = document.getElementById('fileManagerStatus');
        const insertSelectedFile = document.getElementById('insertSelectedFile');
        const fileUploadInput = document.getElementById('fileUploadInput');
        let selectedFileUrl = '';

        const debounce = (fn, delay = 300) => {
            let timer;

            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn(...args), delay);
            };
        };

        const renderTemplateCards = (templates) => {
            templateList.innerHTML = '';
            confirmTemplateImport.disabled = true;
            selectedTemplateContent = '';

            if (!templates.length) {
                templateFeedback.classList.remove('d-none');
                templateFeedback.textContent = 'Nenhum template encontrado para o filtro aplicado.';
                return;
            }

            templateFeedback.classList.add('d-none');

            templates.forEach((template) => {
                const col = document.createElement('div');
                col.className = 'col-md-6';

                const card = document.createElement('div');
                card.className = 'card h-100 template-card';
                card.innerHTML = `
                    <div class="card-body">
                        <h6 class="card-title d-flex justify-content-between align-items-start">
                            <span>${template.name}</span>
                            <span class="badge bg-light text-dark">ID ${template.id}</span>
                        </h6>
                        <p class="card-text small mb-2 text-muted">${template.description || 'Sem descrição'}</p>
                        <div class="small text-muted">Atualizado em: ${template.updated_at ?? 'Data não informada'}</div>
                    </div>
                `;

                card.addEventListener('click', () => {
                    document.querySelectorAll('.template-card').forEach((item) => item.classList.remove('border-primary'));
                    card.classList.add('border-primary');
                    selectedTemplateContent = template.html_content;
                    confirmTemplateImport.disabled = false;
                });

                col.appendChild(card);
                templateList.appendChild(col);
            });
        };

        const fetchTemplates = (query = '') => {
            templateFeedback.classList.remove('d-none');
            templateFeedback.textContent = 'Carregando templates disponíveis...';

            fetch(`<?= base_url('templates/search') ?>?q=${encodeURIComponent(query)}`)
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        templateFeedback.classList.remove('d-none');
                        templateFeedback.textContent = 'Não foi possível carregar os templates no momento.';
                        return;
                    }

                    renderTemplateCards(data.templates || []);
                })
                .catch(() => {
                    templateFeedback.classList.remove('d-none');
                    templateFeedback.textContent = 'Erro ao buscar templates. Tente novamente.';
                });
        };

        document.getElementById('openTemplateModal').addEventListener('click', () => {
            fetchTemplates('');
            templateSearchInput.value = '';
            templateModal.show();
        });

        templateSearchInput.addEventListener('input', debounce((event) => {
            fetchTemplates(event.target.value);
        }, 400));

        confirmTemplateImport.addEventListener('click', () => {
            if (selectedTemplateContent && typeof window.insertRichHtml === 'function') {
                window.insertRichHtml(selectedTemplateContent);
                templateModal.hide();
            }
        });

        const renderFileCards = (files) => {
            fileManagerList.innerHTML = '';
            insertSelectedFile.disabled = true;
            selectedFileUrl = '';

            if (!files.length) {
                fileManagerList.innerHTML = '<div class="col-12 text-muted">Nenhum arquivo disponível até o momento.</div>';
                return;
            }

            files.forEach((file) => {
                const col = document.createElement('div');
                col.className = 'col-md-4';

                const card = document.createElement('div');
                card.className = 'card h-100 file-card';
                card.innerHTML = `
                    <img src="${file.url}" class="card-img-top" alt="${file.name}">
                    <div class="card-body">
                        <h6 class="card-title text-truncate" title="${file.name}">${file.name}</h6>
                        <p class="card-text small mb-1">${(file.size / 1024).toFixed(1)} KB</p>
                        <p class="card-text small text-muted">${file.updated_at}</p>
                    </div>
                `;

                card.addEventListener('click', () => {
                    document.querySelectorAll('.file-card').forEach((item) => item.classList.remove('border-primary'));
                    card.classList.add('border-primary');
                    selectedFileUrl = file.url;
                    insertSelectedFile.disabled = false;
                });

                col.appendChild(card);
                fileManagerList.appendChild(col);
            });
        };

        const loadFiles = () => {
            fileManagerStatus.textContent = 'Carregando biblioteca...';

            fetch('<?= base_url('files/list') ?>')
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        fileManagerStatus.textContent = 'Não foi possível carregar os arquivos.';
                        return;
                    }

                    fileManagerStatus.textContent = `${data.files.length} arquivo(s) disponíveis.`;
                    renderFileCards(data.files);
                })
                .catch(() => {
                    fileManagerStatus.textContent = 'Erro ao consultar arquivos.';
                });
        };

        document.getElementById('openFileManager').addEventListener('click', () => {
            loadFiles();
            fileManagerModal.show();
        });

        document.getElementById('insertFromManager').addEventListener('click', () => {
            loadFiles();
            fileManagerModal.show();
        });

        insertSelectedFile.addEventListener('click', () => {
            if (selectedFileUrl && typeof window.insertRichHtml === 'function') {
                window.insertRichHtml(`<img src="${selectedFileUrl}" alt="Imagem da biblioteca" style="max-width: 100%; height: auto;">`);
                fileManagerModal.hide();
            }
        });

        const uploadFile = (file) => {
            const formData = new FormData();
            formData.append('file', file);
            fileManagerStatus.textContent = 'Enviando arquivo...';

            fetch('<?= base_url('files/upload') ?>', {
                method: 'POST',
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        fileManagerStatus.textContent = data.error || 'Não foi possível enviar o arquivo.';
                        return;
                    }

                    fileManagerStatus.textContent = 'Upload concluído. Atualizando lista...';
                    loadFiles();
                })
                .catch(() => {
                    fileManagerStatus.textContent = 'Falha ao enviar o arquivo.';
                });
        };

        document.getElementById('triggerFileUpload').addEventListener('click', () => {
            fileUploadInput.click();
        });

        fileUploadInput.addEventListener('change', (event) => {
            const [file] = event.target.files;

            if (file) {
                uploadFile(file);
            }

            event.target.value = '';
        });

        document.getElementById('uploadFromComputer').addEventListener('click', () => {
            fileUploadInput.click();
        });

        document.getElementById('insertFromUrl').addEventListener('click', () => {
            imageUrlModal.show();
        });

        document.getElementById('insertImageUrl').addEventListener('click', () => {
            const url = document.getElementById('imageUrlInput').value.trim();
            const alt = document.getElementById('imageAltInput').value.trim();

            if (!url) {
                return;
            }

            if (typeof window.insertRichHtml === 'function') {
                window.insertRichHtml(`<img src="${url}" alt="${alt || 'Imagem externa'}" style="max-width: 100%; height: auto;">`);
            }

            imageUrlModal.hide();
            document.getElementById('imageUrlInput').value = '';
            document.getElementById('imageAltInput').value = '';
        });

        if (form) {
            form.addEventListener('submit', function() {
                if (typeof window.syncRichEditors === 'function') {
                    window.syncRichEditors();
                }
            });
        }
    });
</script>
<?= $this->endSection() ?>
