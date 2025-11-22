<?php if ($pager->getPageCount() > 1) : ?>
<nav aria-label="Navegação de página">
    <ul class="pagination justify-content-center">
        <?php if ($pager->hasPreviousPage()) : ?>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="Primeira">
                    <span aria-hidden="true">«</span>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getPreviousPage() ?>" aria-label="Anterior">
                    <span aria-hidden="true">‹</span>
                </a>
            </li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link">«</span></li>
            <li class="page-item disabled"><span class="page-link">‹</span></li>
        <?php endif; ?>

        <?php foreach ($pager->links() as $link): ?>
            <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
            </li>
        <?php endforeach; ?>

        <?php if ($pager->hasNextPage()) : ?>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getNextPage() ?>" aria-label="Próxima">
                    <span aria-hidden="true">›</span>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getLast() ?>" aria-label="Última">
                    <span aria-hidden="true">»</span>
                </a>
            </li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link">›</span></li>
            <li class="page-item disabled"><span class="page-link">»</span></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
