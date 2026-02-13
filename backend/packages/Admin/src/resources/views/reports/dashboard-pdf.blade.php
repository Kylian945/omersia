<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Dashboard - Omersia</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            background: #1f2937;
            color: white;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24pt;
            margin: 0 0 5px 0;
        }

        .header p {
            font-size: 12pt;
            margin: 0;
        }

        .info-section {
            margin-bottom: 30px;
            padding: 15px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            color: #6b7280;
            display: inline-block;
            width: 120px;
        }

        .info-value {
            color: #1f2937;
        }

        .stats-section {
            margin-bottom: 30px;
        }

        .stats-title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 15px;
            color: #1f2937;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 5px;
        }

        .stats-container {
            margin-bottom: 20px;
        }

        .stat-card {
            background: #f9fafb;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #e5e7eb;
        }

        .stat-label {
            font-size: 9pt;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 18pt;
            font-weight: bold;
            color: #1f2937;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #f9fafb;
            padding: 10px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 10px;
            font-size: 10pt;
            border-bottom: 1px solid #f3f4f6;
        }

        tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9pt;
            color: #6b7280;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Dashboard</h1>
        <p>Omersia - E-commerce</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Période :</span>
            <span class="info-value">{{ $periodLabel }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Du :</span>
            <span class="info-value">{{ $startDate->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Au :</span>
            <span class="info-value">{{ $endDate->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Généré le :</span>
            <span class="info-value">{{ now()->format('d/m/Y à H:i') }}</span>
        </div>
    </div>

    <div class="stats-section">
        <div class="stats-title">Statistiques globales</div>
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-label">Total des ventes</div>
                <div class="stat-value">{{ number_format((float) ($periodSales ?? 0), 2, ',', ' ') }} €</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Commandes</div>
                <div class="stat-value">{{ $periodOrdersCount }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Panier moyen</div>
                <div class="stat-value">{{ number_format((float) ($averageOrderValue ?? 0), 2, ',', ' ') }} €</div>
            </div>
        </div>
    </div>

    <div class="stats-section">
        <div class="stats-title">Détail par jour</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th style="text-align: right;">Nombre de commandes</th>
                    <th style="text-align: right;">Chiffre d'affaires</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordersInPeriod as $order)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</td>
                        <td style="text-align: right;">{{ $order->count }}</td>
                        <td style="text-align: right;">{{ number_format((float) ($order->total ?? 0), 2, ',', ' ') }} €</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center; color: #6b7280;">
                            Aucune commande sur cette période
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Document généré automatiquement par Omersia</p>
    </div>
</body>
</html>
