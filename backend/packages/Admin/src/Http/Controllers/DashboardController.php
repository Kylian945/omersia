<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Omersia\Catalog\Models\Cart;
use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        // Récupérer le filtre de période (par défaut : month)
        $period = request('period', 'month'); // day, week, month, year, custom
        $compare = request('compare', true); // Comparer avec la période précédente

        // Définir les dates selon la période
        $now = Carbon::now();

        // Vérifier si on a des dates personnalisées
        if ($period === 'custom' && request('date_from') && request('date_to')) {
            $startDate = Carbon::parse(request('date_from'))->startOfDay();
            $endDate = Carbon::parse(request('date_to'))->endOfDay();

            // Calculer la période précédente (même durée)
            $diffInDays = $startDate->diffInDays($endDate);
            $previousEndDate = $startDate->copy()->subDay()->endOfDay();
            $previousStartDate = $previousEndDate->copy()->subDays($diffInDays)->startOfDay();

            $periodLabel = 'Période personnalisée';
            $compareLabel = 'période précédente';
        } else {
            switch ($period) {
                case 'day':
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    $previousStartDate = Carbon::yesterday();
                    $previousEndDate = Carbon::yesterday()->endOfDay();
                    $periodLabel = 'Aujourd\'hui';
                    $compareLabel = 'hier';
                    break;
                case 'week':
                    $startDate = Carbon::now()->startOfWeek();
                    $endDate = Carbon::now()->endOfWeek();
                    $previousStartDate = Carbon::now()->subWeek()->startOfWeek();
                    $previousEndDate = Carbon::now()->subWeek()->endOfWeek();
                    $periodLabel = 'Cette semaine';
                    $compareLabel = 'semaine dernière';
                    break;
                case 'year':
                    $startDate = Carbon::now()->startOfYear();
                    $endDate = Carbon::now()->endOfYear();
                    $previousStartDate = Carbon::now()->subYear()->startOfYear();
                    $previousEndDate = Carbon::now()->subYear()->endOfYear();
                    $periodLabel = 'Cette année';
                    $compareLabel = 'année dernière';
                    break;
                case 'month':
                default:
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    $previousStartDate = Carbon::now()->subMonth()->startOfMonth();
                    $previousEndDate = Carbon::now()->subMonth()->endOfMonth();
                    $periodLabel = 'Ce mois';
                    $compareLabel = 'mois dernier';
                    break;
            }
        }

        // Commandes confirmées et payées de la période
        $currentPeriodQuery = Order::whereBetween('placed_at', [$startDate, $endDate])
            ->confirmed()
            ->where('payment_status', 'paid');

        $periodSales = (float) $currentPeriodQuery->sum('total');
        $periodOrdersCount = (int) $currentPeriodQuery->count();

        // Commandes confirmées et payées de la période précédente (pour comparaison)
        $previousPeriodSales = (float) Order::whereBetween('placed_at', [$previousStartDate, $previousEndDate])
            ->confirmed()
            ->where('payment_status', 'paid')
            ->sum('total');

        $salesDiffPercent = null;
        $salesDiffDirection = null; // 'up' | 'down' | null

        // Calculer la différence de ventes
        if ($previousPeriodSales > 0) {
            // Il y avait des ventes la période précédente, on peut calculer le pourcentage
            $diff = $periodSales - $previousPeriodSales;
            $salesDiffPercent = ($diff / $previousPeriodSales) * 100;
            $salesDiffDirection = $diff >= 0 ? 'up' : 'down';
            $salesDiffPercent = abs($salesDiffPercent);
        } elseif ($periodSales > 0 && $previousPeriodSales == 0) {
            // Pas de ventes la période précédente mais des ventes maintenant = +100%
            $salesDiffPercent = 100;
            $salesDiffDirection = 'up';
        }
        // Si pas de ventes la période précédente ni maintenant, on laisse null

        $averageOrderValue = $periodOrdersCount > 0
            ? $periodSales / $periodOrdersCount
            : 0;

        // Produits actifs
        $productsCount = Product::where('is_active', true)->count();

        // Dernières commandes
        $lastOrders = Order::latest('placed_at')
            ->confirmed()
            ->take(5)
            ->get();

        // Paniers actifs (status = open)
        $activeCartsCount = Cart::where('status', 'open')->count();

        // Graphique : Commandes par période
        $ordersInPeriod = Order::whereBetween('placed_at', [$startDate, $endDate])
            ->confirmed()
            ->selectRaw('DATE(placed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Graphique : Commandes de la période précédente
        $ordersInPreviousPeriod = Order::whereBetween('placed_at', [$previousStartDate, $previousEndDate])
            ->confirmed()
            ->selectRaw('DATE(placed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Créer un tableau avec tous les jours/dates de la période
        $chartLabels = [];
        $chartData = [];
        $chartPreviousData = [];
        $todayIndex = null; // Index de la date du jour dans le tableau

        if ($period === 'day') {
            // Pour un jour : afficher par heure
            for ($hour = 0; $hour < 24; $hour++) {
                $chartLabels[] = $hour.'h';
                // Pour l'instant on affiche juste le total du jour (à améliorer plus tard)
                $chartData[] = $hour === 12 ? $periodOrdersCount : 0;
                $chartPreviousData[] = 0;
            }
            $todayIndex = (int) $now->format('H');
        } elseif ($period === 'week') {
            // Pour une semaine : afficher par jour
            $current = $startDate->copy();
            $previousCurrent = $previousStartDate->copy();
            $index = 0;
            while ($current <= $endDate) {
                $dateString = $current->format('Y-m-d');
                $previousDateString = $previousCurrent->format('Y-m-d');

                $chartLabels[] = $current->locale('fr')->isoFormat('ddd D');
                $chartData[] = $ordersInPeriod->get($dateString)->count ?? 0;
                $chartPreviousData[] = $ordersInPreviousPeriod->get($previousDateString)->count ?? 0;

                if ($current->isSameDay($now)) {
                    $todayIndex = $index;
                }

                $current->addDay();
                $previousCurrent->addDay();
                $index++;
            }
        } elseif ($period === 'year') {
            // Pour une année : afficher par mois
            $ordersInYear = Order::whereBetween('placed_at', [$startDate, $endDate])
                ->confirmed()
                ->selectRaw('MONTH(placed_at) as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            $ordersInPreviousYear = Order::whereBetween('placed_at', [$previousStartDate, $previousEndDate])
                ->confirmed()
                ->selectRaw('MONTH(placed_at) as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            for ($month = 1; $month <= 12; $month++) {
                $chartLabels[] = $months[$month - 1];
                $chartData[] = $ordersInYear->get($month)->count ?? 0;
                $chartPreviousData[] = $ordersInPreviousYear->get($month)->count ?? 0;

                if ($month == (int) $now->format('m')) {
                    $todayIndex = $month - 1;
                }
            }
        } elseif ($period === 'custom') {
            // Pour une période personnalisée : afficher par jour
            $current = $startDate->copy();
            $previousCurrent = $previousStartDate->copy();
            $index = 0;
            while ($current <= $endDate) {
                $dateString = $current->format('Y-m-d');
                $previousDateString = $previousCurrent->format('Y-m-d');

                $chartLabels[] = $current->format('d/m');
                $chartData[] = $ordersInPeriod->get($dateString)->count ?? 0;
                $chartPreviousData[] = $ordersInPreviousPeriod->get($previousDateString)->count ?? 0;

                if ($current->isSameDay($now)) {
                    $todayIndex = $index;
                }

                $current->addDay();
                $previousCurrent->addDay();
                $index++;
            }
        } else {
            // Pour un mois : afficher par jour
            $current = $startDate->copy();
            $previousCurrent = $previousStartDate->copy();
            $index = 0;
            while ($current <= $endDate) {
                $dateString = $current->format('Y-m-d');
                $previousDateString = $previousCurrent->format('Y-m-d');

                $chartLabels[] = $current->day;
                $chartData[] = $ordersInPeriod->get($dateString)->count ?? 0;
                $chartPreviousData[] = $ordersInPreviousPeriod->get($previousDateString)->count ?? 0;

                if ($current->isSameDay($now)) {
                    $todayIndex = $index;
                }

                $current->addDay();
                $previousCurrent->addDay();
                $index++;
            }
        }

        return view('admin::dashboard', compact(
            'periodSales',
            'periodOrdersCount',
            'averageOrderValue',
            'salesDiffPercent',
            'salesDiffDirection',
            'productsCount',
            'lastOrders',
            'activeCartsCount',
            'chartLabels',
            'chartData',
            'chartPreviousData',
            'todayIndex',
            'period',
            'periodLabel',
            'compareLabel',
        ));
    }

    public function activeCartsCount()
    {
        $count = Cart::where('status', 'open')->count();

        return response()->json([
            'count' => $count,
        ]);
    }

    public function export()
    {
        $this->authorize('orders.export');

        $format = request('format', 'csv');
        $period = request('period', 'month');

        // Récupérer les mêmes données que l'index (copier la logique)
        if ($period === 'custom' && request('date_from') && request('date_to')) {
            $startDate = Carbon::parse(request('date_from'))->startOfDay();
            $endDate = Carbon::parse(request('date_to'))->endOfDay();
            $periodLabel = 'Période personnalisée ('.$startDate->format('d/m/Y').' - '.$endDate->format('d/m/Y').')';
        } else {
            switch ($period) {
                case 'day':
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    $periodLabel = 'Aujourd\'hui';
                    break;
                case 'week':
                    $startDate = Carbon::now()->startOfWeek();
                    $endDate = Carbon::now()->endOfWeek();
                    $periodLabel = 'Cette semaine';
                    break;
                case 'year':
                    $startDate = Carbon::now()->startOfYear();
                    $endDate = Carbon::now()->endOfYear();
                    $periodLabel = 'Cette année';
                    break;
                case 'month':
                default:
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    $periodLabel = 'Ce mois';
                    break;
            }
        }

        // Récupérer les statistiques
        $periodSales = (float) Order::whereBetween('placed_at', [$startDate, $endDate])
            ->confirmed()
            ->where('payment_status', 'paid')
            ->sum('total');

        $periodOrdersCount = (int) Order::whereBetween('placed_at', [$startDate, $endDate])
            ->confirmed()
            ->where('payment_status', 'paid')
            ->count();

        $averageOrderValue = $periodOrdersCount > 0 ? $periodSales / $periodOrdersCount : 0;

        // Récupérer les commandes par jour
        $ordersInPeriod = Order::whereBetween('placed_at', [$startDate, $endDate])
            ->confirmed()
            ->selectRaw('DATE(placed_at) as date, COUNT(*) as count, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $filename = 'rapport-dashboard-'.$startDate->format('Y-m-d').'-'.$endDate->format('Y-m-d');

        switch ($format) {
            case 'pdf':
                return $this->exportPDF($periodLabel, $startDate, $endDate, $periodSales, $periodOrdersCount, $averageOrderValue, $ordersInPeriod, $filename);
            case 'txt':
                return $this->exportTXT($periodLabel, $startDate, $endDate, $periodSales, $periodOrdersCount, $averageOrderValue, $ordersInPeriod, $filename);
            case 'csv':
            default:
                return $this->exportCSV($periodLabel, $startDate, $endDate, $periodSales, $periodOrdersCount, $averageOrderValue, $ordersInPeriod, $filename);
        }
    }

    private function exportCSV($periodLabel, $startDate, $endDate, $periodSales, $periodOrdersCount, $averageOrderValue, $ordersInPeriod, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
        ];

        $callback = function () use ($periodLabel, $startDate, $endDate, $periodSales, $periodOrdersCount, $averageOrderValue, $ordersInPeriod) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // En-tête du rapport
            fputcsv($file, ['Rapport Dashboard - Omersia']);
            fputcsv($file, ['Période', $periodLabel]);
            fputcsv($file, ['Du', $startDate->format('d/m/Y')]);
            fputcsv($file, ['Au', $endDate->format('d/m/Y')]);
            fputcsv($file, ['Généré le', now()->format('d/m/Y H:i')]);
            fputcsv($file, []);

            // Statistiques globales
            fputcsv($file, ['Statistiques globales']);
            fputcsv($file, ['Total des ventes', number_format($periodSales, 2, ',', ' ').' €']);
            fputcsv($file, ['Nombre de commandes', $periodOrdersCount]);
            fputcsv($file, ['Panier moyen', number_format($averageOrderValue, 2, ',', ' ').' €']);
            fputcsv($file, []);

            // Détail par jour
            fputcsv($file, ['Détail par jour']);
            fputcsv($file, ['Date', 'Nombre de commandes', 'Chiffre d\'affaires']);

            foreach ($ordersInPeriod as $order) {
                fputcsv($file, [
                    Carbon::parse($order->date)->format('d/m/Y'),
                    $order->count,
                    number_format($order->total, 2, ',', ' ').' €',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportTXT($periodLabel, $startDate, $endDate, $periodSales, $periodOrdersCount, $averageOrderValue, $ordersInPeriod, $filename)
    {
        $content = "═══════════════════════════════════════════════════════════════\n";
        $content .= "                 RAPPORT DASHBOARD - OMERSIA                   \n";
        $content .= "═══════════════════════════════════════════════════════════════\n\n";

        $content .= "Période : {$periodLabel}\n";
        $content .= 'Du      : '.$startDate->format('d/m/Y')."\n";
        $content .= 'Au      : '.$endDate->format('d/m/Y')."\n";
        $content .= 'Généré  : '.now()->format('d/m/Y à H:i')."\n\n";

        $content .= "───────────────────────────────────────────────────────────────\n";
        $content .= "                   STATISTIQUES GLOBALES                       \n";
        $content .= "───────────────────────────────────────────────────────────────\n\n";

        $content .= 'Total des ventes      : '.number_format($periodSales, 2, ',', ' ')." €\n";
        $content .= "Nombre de commandes   : {$periodOrdersCount}\n";
        $content .= 'Panier moyen          : '.number_format($averageOrderValue, 2, ',', ' ')." €\n\n";

        $content .= "───────────────────────────────────────────────────────────────\n";
        $content .= "                      DÉTAIL PAR JOUR                          \n";
        $content .= "───────────────────────────────────────────────────────────────\n\n";

        $content .= sprintf("%-15s %-20s %-20s\n", 'Date', 'Commandes', 'CA');
        $content .= str_repeat('-', 60)."\n";

        foreach ($ordersInPeriod as $order) {
            $content .= sprintf(
                "%-15s %-20s %-20s\n",
                Carbon::parse($order->date)->format('d/m/Y'),
                $order->count,
                number_format($order->total, 2, ',', ' ').' €'
            );
        }

        $content .= "\n═══════════════════════════════════════════════════════════════\n";

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.txt"',
        ]);
    }

    private function exportPDF($periodLabel, $startDate, $endDate, $periodSales, $periodOrdersCount, $averageOrderValue, $ordersInPeriod, $filename)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin::reports.dashboard-pdf', compact(
            'periodLabel',
            'startDate',
            'endDate',
            'periodSales',
            'periodOrdersCount',
            'averageOrderValue',
            'ordersInPeriod'
        ));

        return $pdf->download($filename.'.pdf');
    }
}
