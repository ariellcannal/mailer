(function() {
    function initializeSelect2() {
        if (typeof $.fn.select2 === 'undefined') {
            return;
        }

        $('.select2').each(function() {
            const placeholder = $(this).data('placeholder') || 'Selecione';
            $(this).select2({ width: '100%', placeholder: placeholder, allowClear: true });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initializeSelect2();
    });
})();
