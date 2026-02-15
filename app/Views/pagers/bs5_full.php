<?php if ($pager->getPageCount() > 1) : ?>
    <?php
    $currentPage = $pager->getCurrentPageNumber();
    $totalPages  = $pager->getPageCount();
    $perPage = $pager->getPerPage();
    
    // Calcular páginas anteriores e seguintes
    $previousPages = [];
    for ($i = max(1, $currentPage - 3); $i < $currentPage; $i++) {
        $previousPages[] = $i;
    }
    
    $nextPages = [];
    for ($i = $currentPage + 1; $i <= min($totalPages, $currentPage + 3); $i++) {
        $nextPages[] = $i;
    }
    
    // Função helper para construir URL com número de página
    function buildPageUrl($pager, $pageNum) {
        $uri = current_url(true);
        $query = $_GET;
        $query['page'] = $pageNum;
        return $uri->setQueryArray($query)->__toString();
    }
    ?>

    <div class="d-flex justify-content-end align-items-center mt-3">
        <!-- Navegação de páginas -->
        <nav aria-label="Navegação de página">
            <ul class="pagination pagination-sm mb-0 me-1">
                <!-- Primeira -->
                <li class="page-item <?= $pager->hasPreviousPage() ? '' : 'disabled' ?>">
                    <?php if ($pager->hasPreviousPage()) : ?>
                        <a class="page-link" href="<?= $pager->getFirst() ?>">Primeira</a>
                    <?php else : ?>
                        <span class="page-link">Primeira</span>
                    <?php endif; ?>
                </li>

                <!-- Anterior -->
                <li class="page-item <?= $pager->hasPreviousPage() ? '' : 'disabled' ?>">
                    <?php if ($pager->hasPreviousPage()) : ?>
                        <a class="page-link" href="<?= $pager->getPreviousPage() ?>">Anterior</a>
                    <?php else : ?>
                        <span class="page-link">Anterior</span>
                    <?php endif; ?>
                </li>

                <!-- 3 páginas anteriores -->
                <?php foreach ($previousPages as $page): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPageUrl($pager, $page) ?>"><?= $page ?></a>
                    </li>
                <?php endforeach; ?>

                <!-- Página atual -->
                <li class="page-item active">
                    <span class="page-link">
                        <?= $currentPage ?>
                    </span>
                </li>

                <!-- 3 páginas seguintes -->
                <?php foreach ($nextPages as $page): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPageUrl($pager, $page) ?>"><?= $page ?></a>
                    </li>
                <?php endforeach; ?>

                <!-- Próxima -->
                <li class="page-item <?= $pager->hasNextPage() ? '' : 'disabled' ?>">
                    <?php if ($pager->hasNextPage()) : ?>
                        <a class="page-link" href="<?= $pager->getNextPage() ?>">Próxima</a>
                    <?php else : ?>
                        <span class="page-link">Próxima</span>
                    <?php endif; ?>
                </li>

                <!-- Última -->
                <li class="page-item <?= $pager->hasNextPage() ? '' : 'disabled' ?>">
                    <?php if ($pager->hasNextPage()) : ?>
                        <a class="page-link" href="<?= $pager->getLast() ?>">Última (<?= $totalPages ?>)</a>
                    <?php else : ?>
                        <span class="page-link">Última (<?= $totalPages ?>)</span>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>

        <!-- Button dropdown de itens por página -->
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-list"></i> <?= $perPage ?> por página
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item <?= $perPage == 25 ? 'active' : '' ?>" href="#" onclick="changePerPage(25); return false;">25 por página</a></li>
                <li><a class="dropdown-item <?= $perPage == 50 ? 'active' : '' ?>" href="#" onclick="changePerPage(50); return false;">50 por página</a></li>
                <li><a class="dropdown-item <?= $perPage == 100 ? 'active' : '' ?>" href="#" onclick="changePerPage(100); return false;">100 por página</a></li>
                <li><a class="dropdown-item <?= $perPage == 200 ? 'active' : '' ?>" href="#" onclick="changePerPage(200); return false;">200 por página</a></li>
            </ul>
        </div>
    </div>

    <script>
    function changePerPage(perPage) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage);
        url.searchParams.delete('page'); // Volta para página 1
        window.location.href = url.toString();
    }
    </script>
<?php endif; ?>
