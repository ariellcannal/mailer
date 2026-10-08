<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="card-body">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Templates do WhatsApp</h4>
            <div class="btn-group" role="group" aria-label="Ações de templates">
                <form action="<?= base_url('whatsapp/templates/sync') ?>" method="POST">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-primary" type="submit"><i class="fas fa-sync" aria-hidden="true"></i><span>Sincronizar Meta</span></button>
                </form>
                <a href="<?= base_url('whatsapp/templates/create') ?>" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i><span>Novo template</span></a>
            </div>
        </header>
        <?php if (session('success')): ?><div class="alert alert-success"><?= esc(session('success')) ?></div><?php endif; ?>
        <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Nome</th><th>Categoria</th><th>Idioma</th><th>Status Meta</th><th>Ações</th></tr></thead>
                <tbody>
                <?php if (empty($templates)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhum template cadastrado ou sincronizado.</td></tr>
                <?php else: ?>
                    <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><?= esc($template['name']) ?></td>
                            <td><?= esc($template['category']) ?></td>
                            <td><?= esc($template['language']) ?></td>
                            <td><span class="badge <?= $template['status'] === 'approved' ? 'bg-success' : ($template['status'] === 'rejected' ? 'bg-danger' : 'bg-secondary') ?>"><?= esc(ucfirst($template['status'])) ?></span></td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Ações do template">
                                    <a href="<?= base_url('whatsapp/templates/edit/' . $template['id']) ?>" class="btn btn-outline-primary" title="Editar template" aria-label="Editar template"><i class="fas fa-edit" aria-hidden="true"></i></a>
                                    <?php if ($template['status'] === 'draft'): ?>
                                        <form action="<?= base_url('whatsapp/templates/submit/' . $template['id']) ?>" method="POST">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-secondary" title="Enviar à Meta" aria-label="Enviar à Meta"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?= $this->endSection() ?>