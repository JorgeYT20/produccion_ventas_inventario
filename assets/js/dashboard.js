document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('graficoVentas');

    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                // labelsVentas y dataVentas vienen del script en index.php
                labels: labelsVentas,
                datasets: [{
                    label: 'Total Ventas (S/.)',
                    data: dataVentas,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderRadius: 8,
                    borderWidth: 1
                    
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});