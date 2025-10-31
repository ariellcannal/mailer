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
