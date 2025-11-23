(function() {
    let performanceChart;

    function getChartUrl(canvas) {
        return canvas?.dataset.chartUrl || '';
    }

    function buildChart(canvas) {
        const context = canvas.getContext('2d');
        performanceChart = new Chart(context, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Enviados', data: [], borderColor: '#3498db', backgroundColor: 'rgba(52, 152, 219, 0.1)', tension: 0.4 },
                    { label: 'Aberturas', data: [], borderColor: '#2ecc71', backgroundColor: 'rgba(46, 204, 113, 0.1)', tension: 0.4 },
                    { label: 'Cliques', data: [], borderColor: '#e74c3c', backgroundColor: 'rgba(231, 76, 60, 0.1)', tension: 0.4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function updateChart(canvas) {
        const url = getChartUrl(canvas);
        const period = document.getElementById('chartPeriod')?.value || '7d';
        if (!url) {
            return;
        }

        $.ajax({
            url: url,
            data: { period: period },
            success: function(response) {
                const labels = response.sends.map(function(item) { return item.date; });
                const sendsData = response.sends.map(function(item) { return item.count; });
                const opensData = labels.map(function(date) {
                    const found = response.opens.find(function(item) { return item.date === date; });
                    return found ? found.count : 0;
                });
                const clicksData = labels.map(function(date) {
                    const found = response.clicks.find(function(item) { return item.date === date; });
                    return found ? found.count : 0;
                });

                performanceChart.data.labels = labels;
                performanceChart.data.datasets[0].data = sendsData;
                performanceChart.data.datasets[1].data = opensData;
                performanceChart.data.datasets[2].data = clicksData;
                performanceChart.update();
            },
            error: function() {
                alertify.error('Erro ao carregar dados do gráfico');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('performanceChart');
        if (!canvas) {
            return;
        }

        buildChart(canvas);
        updateChart(canvas);

        const periodSelect = document.getElementById('chartPeriod');
        if (periodSelect) {
            periodSelect.addEventListener('change', function() { updateChart(canvas); });
        }
    });
})();
