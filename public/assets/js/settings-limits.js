(function() {
    function renderLimits(container, response) {
        if (!response || response.success === false) {
            container.innerHTML = '<div class="alert alert-danger">' + (response?.message || 'Erro ao consultar limites.') + '</div>';
            return;
        }

        container.innerHTML = `
            <div class="row">
                <div class="col-md-3"><strong>Limite 24h:</strong> ${response.max24HourSend}</div>
                <div class="col-md-3"><strong>Taxa Máxima:</strong> ${response.maxSendRate}</div>
                <div class="col-md-3"><strong>Enviados 24h:</strong> ${response.sentLast24Hours}</div>
                <div class="col-md-3"><strong>Restante:</strong> ${response.remaining}</div>
            </div>
        `;
    }

    function requestLimits(button, container) {
        const url = button.dataset.sesUrl;
        if (!url) {
            return;
        }

        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Consultando...';

        $.getJSON(url)
            .done(function(response) { renderLimits(container, response); })
            .fail(function() { renderLimits(container, { success: false }); })
            .always(function() {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-sync"></i> Consultar limites SES';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const button = document.getElementById('btnSesLimits');
        const container = document.getElementById('sesLimits');

        if (button && container) {
            button.addEventListener('click', function() {
                requestLimits(button, container);
            });
        }
    });
})();
