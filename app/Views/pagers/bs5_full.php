<?php if ($pager->getPageCount() > 1) : ?>
    <?php
    $currentPage = $pager->getCurrentPage();
    $totalPages  = $pager->getPageCount();
    ?>

    <nav aria-label="Navegação de página">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $pager->hasPreviousPage() ? '' : 'disabled' ?>">
                <?php if ($pager->hasPreviousPage()) : ?>
                    <a class="page-link" href="<?= $pager->getFirst() ?>">Primeira</a>
                <?php else : ?>
                    <span class="page-link">Primeira</span>
                <?php endif; ?>
            </li>

            <li class="page-item <?= $pager->hasPreviousPage() ? '' : 'disabled' ?>">
                <?php if ($pager->hasPreviousPage()) : ?>
                    <a class="page-link" href="<?= $pager->getPreviousPage() ?>">Anterior</a>
                <?php else : ?>
                    <span class="page-link">Anterior</span>
                <?php endif; ?>
            </li>

            <li class="page-item active">
                <span class="page-link">
                    <?= $currentPage ?> / <?= $totalPages ?>
                </span>
            </li>

            <li class="page-item <?= $pager->hasNextPage() ? '' : 'disabled' ?>">
                <?php if ($pager->hasNextPage()) : ?>
                    <a class="page-link" href="<?= $pager->getNextPage() ?>">Próxima</a>
                <?php else : ?>
                    <span class="page-link">Próxima</span>
                <?php endif; ?>
            </li>

            <li class="page-item <?= $pager->hasNextPage() ? '' : 'disabled' ?>">
                <?php if ($pager->hasNextPage()) : ?>
                    <a class="page-link" href="<?= $pager->getLast() ?>">Última (<?= $totalPages ?>)</a>
                <?php else : ?>
                    <span class="page-link">Última (<?= $totalPages ?>)</span>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
<?php endif; ?>
