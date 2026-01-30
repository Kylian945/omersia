import { Chart, registerables } from 'chart.js';

// Enregistrer tous les composants Chart.js
Chart.register(...registerables);

// Initialiser le graphique quand les données sont disponibles
export function initOrdersChart(labels, data, previousData, todayIndex) {
    const ctx = document.getElementById('ordersChart');
    if (!ctx) return;

    // Créer deux segments pour la période actuelle : avant/jusqu'à aujourd'hui (solid) et après aujourd'hui (dashed)
    const datasets = [];

    // Dataset 1 : Période actuelle jusqu'à aujourd'hui (ligne solide)
    const currentDataSolid = data.map((value, index) => index <= todayIndex ? value : null);
    datasets.push({
        label: 'Période actuelle',
        data: currentDataSolid,
        borderColor: 'rgb(58, 190, 249)',
        backgroundColor: 'rgba(0, 0, 0, 0)',
        borderWidth: 2,
        fill: false,
        tension: 0.3,
        pointRadius: 0,
        pointHoverRadius: 5,
        pointBackgroundColor: 'rgb(58, 190, 249)',
        pointBorderColor: 'rgb(255, 255, 255)',
        pointBorderWidth: 2,
        spanGaps: false,
    });

    // Dataset 2 : Période actuelle après aujourd'hui (ligne en pointillés)
    if (todayIndex !== null && todayIndex < data.length - 1) {
        const currentDataDashed = data.map((value, index) => index >= todayIndex ? value : null);
        datasets.push({
            label: 'Actuelle',
            data: currentDataDashed,
            borderColor: 'rgb(58, 190, 249)',
            backgroundColor: 'rgba(0, 0, 0, 0)',
            borderWidth: 2,
            borderDash: [5, 5],
            fill: false,
            tension: 0.3,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointBackgroundColor: 'rgb(58, 190, 249)',
            pointBorderColor: 'rgb(255, 255, 255)',
            pointBorderWidth: 2,
            spanGaps: false,
            // Masquer ce dataset de la légende
            hidden: false,
            skipNull: true,
        });
    }

    // Dataset 3 : Période précédente (ligne en pointillés)
    datasets.push({
        label: 'Période précédente',
        data: previousData,
        borderColor: 'rgb(156, 163, 175)',
        backgroundColor: 'rgba(0, 0, 0, 0)',
        borderWidth: 2,
        borderDash: [5, 5],
        fill: false,
        tension: 0.3,
        pointRadius: 0,
        pointHoverRadius: 5,
        pointBackgroundColor: 'rgb(156, 163, 175)',
        pointBorderColor: 'rgb(255, 255, 255)',
        pointBorderWidth: 2,
    });

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 8
                        },
                        filter: function(legendItem) {
                            // Masquer le deuxième dataset "Actuelle" (celui en pointillés pour le futur)
                            // On garde uniquement les datasets avec des index 0 et 2 (ou le dernier si pas de futur)
                            return legendItem.datasetIndex !== 1;
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    displayColors: true,
                    callbacks: {
                        title: function(context) {
                            return 'Jour ' + context[0].label;
                        },
                        label: function(context) {
                            // Masquer le dataset avec l'index 1 (période actuelle en pointillés/futur)
                            if (context.datasetIndex === 1) return null;

                            // Afficher uniquement la valeur sans le label du dataset
                            if (context.parsed.y === null) return null;
                            return context.parsed.y + ' commande(s)';
                        },
                        labelColor: function(context) {
                            // Masquer aussi la couleur pour le dataset 1
                            if (context.datasetIndex === 1) return null;

                            return {
                                borderColor: context.dataset.borderColor,
                                backgroundColor: context.dataset.borderColor,
                            };
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 10
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 10
                        },
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 15
                    },
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

// Exposer la fonction globalement pour pouvoir l'appeler depuis Blade
window.initOrdersChart = initOrdersChart;
