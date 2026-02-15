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
    
    // Construir URL com per_page
    $currentUrl = current_url(true);
    $queryParams = $_GET;
    ?>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <!-- Navegação de páginas -->
        <nav aria-label="Navegação de página">
            <ul class="pagination pagination-sm mb-0">
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
                        <a class="page-link" href="<?= $pager->getPageURI($page) ?>"><?= $page ?></a>
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
                        <a class="page-link" href="<?= $pager->getPageURI($page) ?>"><?= $page ?></a>
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

        <!-- Select de itens por página -->
        <div class="d-flex align-items-center gap-2">
            <label class="mb-0 text-muted small">Itens por página:</label>
            <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                <option value="200" <?= $perPage == 200 ? 'selected' : '' ?>>200</option>
            </select>
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
