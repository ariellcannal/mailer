<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/whatsapp.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/whatsapp.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $isEdit = !empty($template['id']); ?>
<section class="card">
    <div class="card-body">
        <h4 class="mb-4"><?= $isEdit ? 'Editar template WhatsApp' : 'Novo template WhatsApp' ?></h4>
        <div class="row g-4">
            <div class="col-xl-7">
                <form action="<?= base_url('whatsapp/templates/store') ?>" method="POST" enctype="multipart/form-data" id="whatsappTemplateForm">
                    <?= csrf_field() ?>
                    <?php if ($isEdit): ?><input type="hidden" name="template_id" value="<?= (int) $template['id'] ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="waTemplateName">Nome do template</label>
                        <input id="waTemplateName" name="name" class="form-control" maxlength="128" pattern="[a-z0-9_]{3,128}" value="<?= esc(old('name', $template['name'] ?? '')) ?>" required data-wa-preview-name>
                        <small class="text-muted">Use letras minúsculas, números e underline.</small>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="waTemplateCategory">Categoria</label>
                            <select id="waTemplateCategory" name="category" class="form-select" data-wa-preview-category>
                                <?php foreach (['UTILITY' => 'Utilidade', 'MARKETING' => 'Marketing', 'AUTHENTICATION' => 'Autenticação'] as $value => $label): ?>
                                    <option value="<?= esc($value) ?>" <?= old('category', $template['category'] ?? 'UTILITY') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="waTemplateLanguage">Idioma</label>
                            <select id="waTemplateLanguage" name="language" class="form-select">
                                <?php foreach (['pt_BR' => 'Português (Brasil)', 'en_US' => 'English (US)', 'es_ES' => 'Español'] as $languageCode => $languageLabel): ?>
                                    <option value="<?= esc($languageCode) ?>" <?= old('language', $template['language'] ?? 'pt_BR') === $languageCode ? 'selected' : '' ?>><?= esc($languageLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="waHeaderType">Tipo de cabeçalho</label>
                        <select id="waHeaderType" name="header_type" class="form-select" data-wa-header-type>
                            <?php foreach (['NONE' => 'Sem cabeçalho', 'TEXT' => 'Texto', 'IMAGE' => 'Mídia (imagem)'] as $value => $label): ?>
                                <option value="<?= esc($value) ?>" <?= old('header_type', $template['header_type'] ?? 'NONE') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" data-wa-header-field>
                        <label class="form-label" for="waHeaderContent">Conteúdo do cabeçalho</label>
                        <input id="waHeaderContent" name="header_content" class="form-control" maxlength="1024" value="<?= esc(old('header_content', $template['header_content'] ?? '')) ?>" data-wa-preview-header>
                        <small class="text-muted" data-wa-header-hint>Texto curto exibido acima da mensagem.</small>
                    </div>
                    <div class="mb-3 d-none" data-wa-media-field>
                        <label class="form-label" for="waHeaderMedia">Imagem do cabeçalho</label>
                        <input id="waHeaderMedia" name="header_media" type="file" class="form-control" accept="image/jpeg,image/png" data-wa-preview-media-file>
                        <small class="text-muted">JPEG ou PNG, até 5 MB. A mídia é enviada à Meta ao salvar.</small>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0" for="waBodyContent">Corpo da mensagem</label>
                            <span class="text-muted small"><span data-wa-body-count>0</span>/1024</span>
                        </div>
                        <textarea id="waBodyContent" name="body_content" class="form-control" rows="7" maxlength="1024" required data-wa-preview-body><?= esc(old('body_content', $template['body_content'] ?? '')) ?></textarea>
                        <small class="text-muted">Use variáveis Meta como {{1}} para personalizar o texto.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="waFooterContent">Rodapé (opcional)</label>
                        <input id="waFooterContent" name="footer_content" class="form-control" maxlength="60" value="<?= esc(old('footer_content', $template['footer_content'] ?? '')) ?>" data-wa-preview-footer>
                    </div>
                    <section class="mb-4" aria-labelledby="waActionsTitle">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label id="waActionsTitle" class="form-label mb-0">Botões de ação (opcional)</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addWaButton"><i class="fas fa-plus" aria-hidden="true"></i><span>Adicionar botão</span></button>
                        </div>
                        <div id="waButtonFields" class="wa-button-fields"></div>
                        <input type="hidden" id="waButtonsJson" name="buttons" value="<?= esc(old('buttons', $template['buttons'] ?? '[]')) ?>">
                        <small class="text-muted">Até três ações por template.</small>
                    </section>
                    <div class="btn-group" role="group" aria-label="Ações do template">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save" aria-hidden="true"></i><span>Salvar rascunho</span></button>
                        <a href="<?= base_url('whatsapp/templates') ?>" class="btn btn-neutral">Cancelar</a>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <section class="wa-preview-panel" aria-live="polite">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Prévia</h5>
                        <span class="badge bg-secondary" data-wa-preview-category-label>Utilidade</span>
                    </div>
                    <div class="wa-preview-canvas">
                        <div class="wa-preview-day">Hoje</div>
                        <article class="wa-preview-message">
                            <div class="wa-preview-media d-none" data-wa-preview-media data-preview-kind="template-image-header" data-object-position="50% 50%">
                                <div class="wa-preview-media-placeholder" data-wa-preview-media-placeholder>
                                    <i class="far fa-image fa-2x" aria-hidden="true"></i>
                                    <span>Mídia</span>
                                </div>
                                <button type="button" class="wa-preview-media-open d-none" data-wa-preview-open-media aria-label="Ampliar imagem do template">
                                    <img alt="" data-wa-preview-image>
                                </button>
                            </div>
                            <div class="fw-semibold d-none" data-wa-preview-header-text></div>
                            <div class="wa-preview-body is-placeholder" data-wa-preview-body-text></div>
                            <small class="wa-preview-footer d-none" data-wa-preview-footer-text></small>
                            <div class="wa-preview-meta"><span class="wa-preview-time">19:46</span><i class="fas fa-check-double wa-preview-checks" aria-label="Enviada e lida"></i></div>
                            <div class="wa-preview-buttons" data-wa-preview-buttons></div>
                        </article>
                    </div>
                    <dialog class="wa-media-viewer" data-wa-media-viewer aria-label="Visualização integral da imagem">
                        <button type="button" class="wa-media-viewer-close" data-wa-media-viewer-close aria-label="Fechar visualização">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                        <img alt="Imagem original do template" data-wa-media-viewer-image>
                    </dialog>
                </section>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/whatsapp-template-preview.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/whatsapp-template-preview.js') ?>" defer></script>
<?= $this->endSection() ?>