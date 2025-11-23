(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('templateForm');
        if (!form) {
            return;
        }
        const htmlField = form.querySelector('textarea[name="html_content"]');
        const feedback = document.getElementById('htmlContentFeedback');

        form.addEventListener('submit', (event) => {
            if (typeof window.syncRichEditors === 'function') {
                window.syncRichEditors();
            }
            const value = (htmlField?.value || '').trim();
            if (value === '') {
                event.preventDefault();
                feedback?.classList.remove('d-none');
                return;
            }
            feedback?.classList.add('d-none');
        });
    });
})();
