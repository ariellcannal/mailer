<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/whatsapp.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/whatsapp.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="card-body">
        <h4 class="mb-4">Novo envio WhatsApp</h4>
        <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif; ?>
        <form action="<?= base_url('whatsapp/store') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="waName">Nome do envio</label>
                    <input id="waName" name="name" class="form-control" maxlength="255" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="waCampaign">Campanha</label>
                    <select id="waCampaign" name="campaign_id" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach ($campaigns as $campaign): ?>
                            <option value="<?= (int) $campaign['id'] ?>" <?= (string) ($selectedCampaignId ?? '') === (string) $campaign['id'] ? 'selected' : '' ?>><?= esc($campaign['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="waTemplate">Template aprovado</label>
                    <select id="waTemplate" name="template_id" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach ($templates as $template): ?>
                            <?php $templateConfig = json_encode([
                                'header_type' => $template['header_type'] ?? 'NONE',
                                'header_content' => $template['header_content'] ?? '',
                                'body_content' => $template['body_content'] ?? '',
                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE); ?>
                            <option value="<?= (int) $template['id'] ?>" data-template="<?= esc($templateConfig, 'attr') ?>" <?= (string) old('template_id', '') === (string) $template['id'] ? 'selected' : '' ?>><?= esc($template['name']) ?> (<?= esc($template['language']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <section id="waVariableEditor" class="wa-variable-editor d-none" aria-live="polite">
                        <h5 class="mb-1">Variáveis do template</h5>
                        <p class="text-secondary small mb-3">Escolha um campo do contato ou informe um texto fixo para cada variável.</p>
                        <div id="waVariableRows" class="wa-variable-rows"></div>
                    </section>
                    <input type="hidden" id="waVariableMapping" name="variable_mapping" value="<?= esc(old('variable_mapping', '{}'), 'attr') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="waNumber">Número de origem</label>
                    <select id="waNumber" name="whatsapp_number_id" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach ($numbers as $number): ?>
                            <option value="<?= (int) $number['id'] ?>"><?= esc($number['verified_name'] ?: $number['display_phone_number']) ?> · <?= esc($number['display_phone_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="waLists">Listas de contatos</label>
                    <select id="waLists" name="contact_lists[]" class="form-select" multiple required>
                        <?php foreach ($contactLists as $list): ?>
                            <option value="<?= (int) $list['id'] ?>"><?= esc($list['name']) ?> (<?= (int) $list['total_contacts'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="waSchedule">Agendar para</label>
                    <input id="waSchedule" name="scheduled_at" type="datetime-local" class="form-control">
                </div>
            </div>
            <div class="btn-group mt-4" role="group" aria-label="Ações do envio">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane" aria-hidden="true"></i><span>Adicionar à fila</span></button>
                <a href="<?= base_url('whatsapp') ?>" class="btn btn-neutral">Cancelar</a>
            </div>
        </form>
    </div>
</section>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/whatsapp-send-variables.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/whatsapp-send-variables.js') ?>" defer></script>
<?= $this->endSection() ?>