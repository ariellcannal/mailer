<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-user"></i> <?= esc($contact['name'] ?: $contact['email']) ?></h4>
            <div class="d-flex gap-2">
                <a href="<?= base_url('contacts/edit/' . $contact['id']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <form action="<?= base_url('contacts/delete/' . $contact['id']) ?>" method="POST" onsubmit="return confirm('Deseja remover este contato?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6>Informações Principais</h6>
                    <p class="mb-1"><strong>Email:</strong> <?= esc($contact['email']) ?></p>
                    <p class="mb-1"><strong>Nome:</strong> <?= esc($contact['name']) ?: '-' ?></p>
                    <p class="mb-1"><strong>Status:</strong>
                        <?= (int) $contact['is_active'] === 1 ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?>
                    </p>
                    <p class="mb-0"><strong>Score de Qualidade:</strong> <?= (int) $contact['quality_score'] ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6>Engajamento</h6>
                    <p class="mb-1"><strong>Total de Aberturas:</strong> <?= (int) $contact['total_opens'] ?></p>
                    <p class="mb-1"><strong>Total de Cliques:</strong> <?= (int) $contact['total_clicks'] ?></p>
                    <p class="mb-1"><strong>Última abertura:</strong> <?= $contact['last_open_date'] ? date('d/m/Y H:i', strtotime($contact['last_open_date'])) : '-' ?></p>
                    <p class="mb-0"><strong>Tempo médio de abertura:</strong> <?= $contact['avg_open_time'] ? round($contact['avg_open_time'] / 60, 1) . ' min' : '-' ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
