const chartCanvas = document.getElementById('expenseChart');

if (chartCanvas && window.chartData && window.chartData.amounts.length > 0) {
    const ctx = chartCanvas.getContext('2d');
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: window.chartData.categories,
            datasets: [{
                data: window.chartData.amounts,
                backgroundColor: [
                    '#ff4d4d', '#269b8a', '#2980b9', '#f1c40f', '#9b59b6', '#e67e22', '#1abc9c'
                ],
                borderWidth: 1,
                borderColor: 'rgba(255, 255, 255, 0.2)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#b2bec3',
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        }
                    }
                }
            }
        }
    });
}