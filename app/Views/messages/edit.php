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
                    <label class="form-label">HTML</label>
                    <textarea name="html_content" class="form-control js-rich-editor" rows="12" required><?= old('html_content', $message['html_content']) ?></textarea>
                </div>
            </div>

            <hr>
            <h5 class="mb-3">Agendamento</h5>
            <?php $canReschedule = ($message['status'] === 'scheduled' && !empty($message['scheduled_at']) && strtotime($message['scheduled_at']) > time()); ?>
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
                    <small class="text-muted">Disponível apenas para mensagens com envio futuro.</small>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="alert <?= $canReschedule ? 'alert-info' : 'alert-secondary' ?> mb-0 w-100" role="status">
                        <?php if ($message['scheduled_at']): ?>
                            Agendado para <?= date('d/m/Y H:i', strtotime($message['scheduled_at'])) ?> (status: <?= esc($message['status']) ?>).
                        <?php else: ?>
                            Nenhum agendamento registrado.
                        <?php endif; ?>
                        <?php if (!$canReschedule && $message['scheduled_at']): ?>
                            <br><strong>Reagendamento indisponível</strong>: apenas datas futuras podem ser alteradas.
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($resendRules)): ?>
                <div class="mb-4">
                    <h6 class="mb-2">Reenvios</h6>
                    <div class="row g-3">
                        <?php $now = time(); ?>
                        <?php foreach ($resendRules as $rule): ?>
                            <?php
                                $isEditable = ($rule['status'] === 'pending'
                                    && !empty($rule['scheduled_at'])
                                    && strtotime($rule['scheduled_at']) > $now);
                            ?>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-primary">Reenvio #<?= (int) $rule['resend_number'] ?></span>
                                            <span class="badge <?= $rule['status'] === 'pending' ? 'bg-info text-dark' : 'bg-secondary' ?>">Status: <?= esc($rule['status']) ?></span>
                                        </div>
                                        <p class="mb-2">Assunto: <strong><?= esc($rule['subject_override']) ?></strong></p>
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
                                            <small class="text-muted">Somente reenvios pendentes com data futura podem ser alterados.</small>
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
