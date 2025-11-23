(function() {
    function toggleSelectAllNotice(visible) {
        const notice = document.getElementById('selectAllNotice');
        if (!notice) {
            return;
        }

        if (visible) {
            notice.classList.remove('d-none');
        } else {
            notice.classList.add('d-none');
        }
    }

    function attachSelectAllHandlers() {
        const masterCheckbox = document.getElementById('selectAll');
        const confirmSelectAll = document.getElementById('confirmSelectAll');
        const flagInput = document.getElementById('selectAllFlag');
        const contactCheckboxes = document.querySelectorAll('input.contact-checkbox');

        if (!masterCheckbox || !flagInput) {
            return;
        }

        masterCheckbox.addEventListener('change', function() {
            const checked = masterCheckbox.checked;
            contactCheckboxes.forEach(function(box) { box.checked = checked; });
            flagInput.value = '0';
            toggleSelectAllNotice(checked);
        });

        if (confirmSelectAll) {
            confirmSelectAll.addEventListener('click', function(event) {
                event.preventDefault();
                flagInput.value = '1';
                toggleSelectAllNotice(false);
                contactCheckboxes.forEach(function(box) { box.checked = true; });
            });
        }

        contactCheckboxes.forEach(function(box) {
            box.addEventListener('change', function() {
                if (!box.checked) {
                    masterCheckbox.checked = false;
                    flagInput.value = '0';
                    toggleSelectAllNotice(false);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        attachSelectAllHandlers();
    });
})();
