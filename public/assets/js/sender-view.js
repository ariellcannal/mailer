(function() {
    function copyValue(value) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value)
                .then(function() { alertify.success('Copiado para a área de transferência'); })
                .catch(function() { alertify.error('Não foi possível copiar o valor'); });
            return;
        }

        const tempInput = document.createElement('input');
        tempInput.value = value;
        document.body.appendChild(tempInput);
        tempInput.select();
        try {
            document.execCommand('copy');
            alertify.success('Copiado para a área de transferência');
        } catch (error) {
            alertify.error('Não foi possível copiar o valor');
        }
        document.body.removeChild(tempInput);
    }

    function handleDnsCheck(button) {
        const url = button.dataset.dnsUrl;
        if (!url) {
            return;
        }

        $.ajax({
            url: url,
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    alertify.success('DNS verificado!');
                    window.location.reload();
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('button[data-copy]').forEach(function(button) {
            button.addEventListener('click', function() {
                copyValue(this.getAttribute('data-copy'));
            });
        });

        const dnsButton = document.getElementById('checkDnsBtn');
        if (dnsButton) {
            dnsButton.addEventListener('click', function() {
                handleDnsCheck(dnsButton);
            });
        }
    });
})();
