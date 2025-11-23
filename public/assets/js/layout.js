(function() {
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.toggle('show');
        }
    }

    function configureAlertify() {
        if (typeof alertify === 'undefined') {
            return;
        }

        alertify.defaults.theme.ok = 'btn btn-primary';
        alertify.defaults.theme.cancel = 'btn btn-secondary';
        alertify.defaults.theme.input = 'form-control';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', toggleSidebar);
        }
        configureAlertify();
    });
})();
