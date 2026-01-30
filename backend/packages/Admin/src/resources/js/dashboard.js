document.addEventListener('DOMContentLoaded', () => {
    // Initialiser le graphique des commandes
    if (typeof window.initOrdersChart === 'function' && window.dashboardConfig) {
        window.initOrdersChart(
            window.dashboardConfig.chartLabels,
            window.dashboardConfig.chartData,
            window.dashboardConfig.chartPreviousData,
            window.dashboardConfig.todayIndex
        );
    }

    // Script "temps réel" pour les paniers actifs
    const el = document.getElementById('active-carts-count');
    if (el && window.dashboardConfig) {
        const refresh = async () => {
            try {
                const res = await fetch(window.dashboardConfig.activeCartsRoute, {
                    headers: {
                        "Accept": "application/json",
                    },
                });

                if (!res.ok) return;
                const data = await res.json();
                el.textContent = typeof data.count === 'number' ? data.count : '0';
            } catch (e) {
                console.error('Failed to refresh active carts count', e);
            }
        };

        refresh();
        setInterval(refresh, 10000); // toutes les 10 secondes
    }

    // Gestion du menu d'export
    const exportButton = document.getElementById('exportButton');
    const exportMenu = document.getElementById('exportMenu');

    if (exportButton && exportMenu) {
        exportButton.addEventListener('click', (e) => {
            e.stopPropagation();
            exportMenu.classList.toggle('hidden');
        });

        // Fermer le menu en cliquant ailleurs
        document.addEventListener('click', () => {
            exportMenu.classList.add('hidden');
        });

        // Empêcher la fermeture quand on clique sur le menu
        exportMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
});
