(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form[data-sync-rich-editor="true"]').forEach((form) => {
            form.addEventListener('submit', () => {
                if (typeof window.syncRichEditors === 'function') {
                    window.syncRichEditors();
                }
            });
        });
    });
})();
