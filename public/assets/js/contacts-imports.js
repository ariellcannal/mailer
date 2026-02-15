/**
 * Contacts Imports Page
 * Auto-refresh quando há importações pendentes ou processando
 */
(function() {
    'use strict';

    // Verificar se há importações pendentes ou processando
    function hasActiveImports() {
        const statusCells = document.querySelectorAll('tbody td:nth-child(4)');
        for (const cell of statusCells) {
            const status = cell.textContent.trim().toLowerCase();
            if (status.includes('pendente') || status.includes('processando')) {
                return true;
            }
        }
        return false;
    }

    // Auto-refresh a cada 10 segundos quando há processo "Processando"
    if (hasActiveImports()) {
        setTimeout(function() {
            location.reload();
        }, 10000); // 10 segundos
    }
})();
