import { subscribeToPrivateEvent } from "../core/realtime-client";

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

    const ordersChartTotalEl = document.getElementById('orders-chart-total');
    const kpiPeriodSalesEl = document.getElementById('kpi-period-sales');
    const kpiSalesDiffEl = document.getElementById('kpi-sales-diff');
    const kpiPeriodOrdersCountEl = document.getElementById('kpi-period-orders-count');
    const kpiAverageOrderValueEl = document.getElementById('kpi-average-order-value');
    const latestOrdersBodyEl = document.getElementById('latest-orders-body');
    const latestOrdersCountEl = document.getElementById('latest-orders-count');
    const euroFormatter = new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    const percentFormatter = new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    });

    const formatEuro = (value) => `${euroFormatter.format(value)} €`;
    const formatPercent = (value) => percentFormatter.format(value);
    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const updateSalesDiff = (diffPercent, diffDirection, compareLabel) => {
        if (!kpiSalesDiffEl) return;

        kpiSalesDiffEl.classList.remove('text-gray-400', 'text-emerald-500', 'text-red-500');

        if (typeof diffPercent === 'number' && Number.isFinite(diffPercent) && (diffDirection === 'up' || diffDirection === 'down')) {
            const sign = diffDirection === 'up' ? '+' : '-';
            const colorClass = diffDirection === 'up' ? 'text-emerald-500' : 'text-red-500';
            kpiSalesDiffEl.classList.add(colorClass);
            kpiSalesDiffEl.textContent = `${sign}${formatPercent(diffPercent)}% vs ${compareLabel}`;
            return;
        }

        kpiSalesDiffEl.classList.add('text-gray-400');
        kpiSalesDiffEl.textContent = 'Pas de comparaison disponible';
    };

    const renderLatestOrders = (orders) => {
        if (!latestOrdersBodyEl) {
            return;
        }

        if (!Array.isArray(orders) || orders.length === 0) {
            latestOrdersBodyEl.innerHTML = `
                <tr>
                    <td class="py-2 text-xxxs text-gray-400" colspan="5">
                        Aucune commande pour le moment.
                    </td>
                </tr>
            `;

            if (latestOrdersCountEl) {
                latestOrdersCountEl.textContent = '0 dernière';
            }
            return;
        }

        latestOrdersBodyEl.innerHTML = orders.map((order) => {
            const displayNumber = escapeHtml(order?.display_number ?? '#—');
            const showUrl = escapeHtml(order?.show_url ?? '#');
            const customerDisplay = escapeHtml(order?.customer_display ?? '—');
            const totalDisplay = escapeHtml(order?.total_display ?? '0,00 €');
            const paymentLabel = escapeHtml(order?.payment_label ?? 'Inconnu');
            const paymentBadgeClass = typeof order?.payment_badge_class === 'string'
                ? order.payment_badge_class
                : 'bg-gray-50 text-gray-600 border-gray-100';
            const statusLabel = escapeHtml(order?.status_label ?? 'Inconnu');
            const statusBadgeClass = typeof order?.status_badge_class === 'string'
                ? order.status_badge_class
                : 'bg-neutral-50 text-neutral-700 border-neutral-100';

            return `
                <tr class="border-b border-gray-50">
                    <td class="py-1">
                        <a href="${showUrl}" class="text-xs font-semibold text-neutral-900 hover:underline">
                            ${displayNumber}
                        </a>
                    </td>
                    <td class="py-1">${customerDisplay}</td>
                    <td class="py-1">${totalDisplay}</td>
                    <td class="py-1">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xxxs border ${paymentBadgeClass}">
                            ${paymentLabel}
                        </span>
                    </td>
                    <td class="py-1">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xxxs border ${statusBadgeClass}">
                            ${statusLabel}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        if (latestOrdersCountEl) {
            const count = orders.length;
            latestOrdersCountEl.textContent = `${count} dernière${count > 1 ? 's' : ''}`;
        }
    };

    // Mise à jour temps réel du graphique des commandes + dernières commandes
    const canRefreshOrdersChart = window.dashboardConfig?.ordersChartRoute && typeof window.initOrdersChart === 'function';
    const canRefreshLatestOrders = window.dashboardConfig?.latestOrdersRoute && latestOrdersBodyEl;
    if (canRefreshOrdersChart || canRefreshLatestOrders) {
        let stopOrdersRealtime = null;
        let chartRefreshTimeout = null;
        let latestOrdersRefreshTimeout = null;

        const refreshOrdersChart = async () => {
            if (!canRefreshOrdersChart) {
                return;
            }

            try {
                const res = await fetch(window.dashboardConfig.ordersChartRoute, {
                    cache: 'no-store',
                    headers: {
                        "Accept": "application/json",
                    },
                });

                if (!res.ok) return;
                const data = await res.json();

                window.initOrdersChart(
                    data.labels ?? [],
                    data.data ?? [],
                    data.previous_data ?? [],
                    data.today_index ?? null
                );

                if (ordersChartTotalEl && typeof data.total_orders === 'number') {
                    ordersChartTotalEl.textContent = String(data.total_orders);
                }

                if (kpiPeriodSalesEl && typeof data.period_sales === 'number') {
                    kpiPeriodSalesEl.textContent = formatEuro(data.period_sales);
                }

                if (kpiPeriodOrdersCountEl && typeof data.period_orders_count === 'number') {
                    kpiPeriodOrdersCountEl.textContent = String(data.period_orders_count);
                }

                if (kpiAverageOrderValueEl) {
                    if (typeof data.period_orders_count === 'number' && data.period_orders_count > 0 && typeof data.average_order_value === 'number') {
                        kpiAverageOrderValueEl.textContent = `Moyenne ${formatEuro(data.average_order_value)}`;
                    } else {
                        kpiAverageOrderValueEl.textContent = 'Moyenne —';
                    }
                }

                const compareLabel = typeof data.compare_label === 'string' && data.compare_label.length > 0
                    ? data.compare_label
                    : (kpiSalesDiffEl?.dataset.compareLabel ?? '');
                if (kpiSalesDiffEl && compareLabel) {
                    kpiSalesDiffEl.dataset.compareLabel = compareLabel;
                }
                updateSalesDiff(data.sales_diff_percent, data.sales_diff_direction, compareLabel);
            } catch (e) {
                console.error('Failed to refresh orders chart', e);
            }
        };

        const scheduleOrdersChartRefresh = () => {
            if (!canRefreshOrdersChart) {
                return;
            }

            if (chartRefreshTimeout) {
                clearTimeout(chartRefreshTimeout);
            }

            chartRefreshTimeout = setTimeout(() => {
                refreshOrdersChart();
            }, 350);
        };

        const refreshLatestOrders = async () => {
            if (!canRefreshLatestOrders) {
                return;
            }

            try {
                const res = await fetch(window.dashboardConfig.latestOrdersRoute, {
                    cache: 'no-store',
                    headers: {
                        "Accept": "application/json",
                    },
                });

                if (!res.ok) return;
                const data = await res.json();
                renderLatestOrders(data.orders ?? []);
            } catch (e) {
                console.error('Failed to refresh latest orders', e);
            }
        };

        const scheduleLatestOrdersRefresh = () => {
            if (!canRefreshLatestOrders) {
                return;
            }

            if (latestOrdersRefreshTimeout) {
                clearTimeout(latestOrdersRefreshTimeout);
            }

            latestOrdersRefreshTimeout = setTimeout(() => {
                refreshLatestOrders();
            }, 250);
        };

        stopOrdersRealtime = subscribeToPrivateEvent(
            'admin.orders',
            'orders.updated',
            () => {
                if (canRefreshOrdersChart) {
                    scheduleOrdersChartRefresh();
                }

                if (canRefreshLatestOrders) {
                    scheduleLatestOrdersRefresh();
                }
            }
        );

        window.addEventListener('beforeunload', () => {
            if (chartRefreshTimeout) {
                clearTimeout(chartRefreshTimeout);
            }
            if (latestOrdersRefreshTimeout) {
                clearTimeout(latestOrdersRefreshTimeout);
            }
            if (typeof stopOrdersRealtime === 'function') {
                stopOrdersRealtime();
            }
        });
    }

    // Script "temps réel" pour les paniers actifs
    const el = document.getElementById('active-carts-count');
    if (el && window.dashboardConfig) {
        let stopRealtime = null;

        const refresh = async () => {
            try {
                const res = await fetch(window.dashboardConfig.activeCartsRoute, {
                    cache: 'no-store',
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

        stopRealtime = subscribeToPrivateEvent(
            'admin.dashboard',
            'dashboard.active-carts.updated',
            (payload) => {
                if (typeof payload?.count === 'number') {
                    el.textContent = String(payload.count);
                }
            }
        );

        refresh();

        window.addEventListener('beforeunload', () => {
            if (typeof stopRealtime === 'function') {
                stopRealtime();
            }
        });
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
