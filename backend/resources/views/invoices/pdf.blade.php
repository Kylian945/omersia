<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 30px;
        }

        .header {
            margin-bottom: 40px;
        }

        .header-row {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .header-col {
            display: table-cell;
            vertical-align: top;
        }

        .header-col.right {
            text-align: right;
        }

        .company-info h1 {
            font-size: 24pt;
            margin: 0 0 5px 0;
            color: #1f2937;
        }

        .company-info p {
            margin: 2px 0;
            font-size: 9pt;
            color: #6b7280;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-info h2 {
            font-size: 28pt;
            margin: 0 0 10px 0;
            color: #1f2937;
            font-weight: bold;
        }

        .invoice-info p {
            margin: 3px 0;
            font-size: 10pt;
        }

        .invoice-number {
            font-size: 14pt;
            font-weight: bold;
            color: #1f2937;
        }

        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }

        .address-block {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding: 15px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .address-block h3 {
            margin: 0 0 10px 0;
            font-size: 11pt;
            color: #1f2937;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 5px;
        }

        .address-block p {
            margin: 3px 0;
            font-size: 9pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        thead {
            background: #1f2937;
            color: white;
        }

        thead th {
            padding: 12px 8px;
            text-align: left;
            font-size: 10pt;
            font-weight: bold;
        }

        thead th.right {
            text-align: right;
        }

        thead th.center {
            text-align: center;
        }

        tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        tbody td {
            padding: 10px 8px;
            font-size: 9pt;
        }

        tbody td.right {
            text-align: right;
        }

        tbody td.center {
            text-align: center;
        }

        .product-name {
            font-weight: bold;
            color: #1f2937;
        }

        .product-sku {
            font-size: 8pt;
            color: #6b7280;
        }

        .totals {
            margin-left: auto;
            width: 350px;
        }

        .totals-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .totals-label {
            display: table-cell;
            text-align: right;
            padding-right: 15px;
            font-size: 10pt;
            color: #6b7280;
        }

        .totals-value {
            display: table-cell;
            text-align: right;
            font-size: 10pt;
            font-weight: bold;
            width: 120px;
        }

        .totals-row.total {
            border-top: 2px solid #1f2937;
            padding-top: 10px;
            margin-top: 10px;
        }

        .totals-row.total .totals-label {
            font-size: 12pt;
            color: #1f2937;
            font-weight: bold;
        }

        .totals-row.total .totals-value {
            font-size: 14pt;
            color: #1f2937;
        }

        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8pt;
            color: #6b7280;
        }

        .payment-info {
            margin-top: 30px;
            padding: 15px;
            background: #f0fdf4;
            border: 1px solid #86efac;
        }

        .payment-info h3 {
            margin: 0 0 10px 0;
            color: #166534;
            font-size: 11pt;
        }

        .payment-info p {
            margin: 3px 0;
            font-size: 9pt;
            color: #166534;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <div class="header-row">
                <div class="header-col">
                    <div class="company-info">
                        <h1>{{ config('app.name', 'Omersia') }}</h1>
                        <p>{{ config('app.company_address', '123 Rue du Commerce') }}</p>
                        <p>{{ config('app.company_city', '75001 Paris, France') }}</p>
                        <p>Email: {{ config('app.company_email', 'contact@omersia.com') }}</p>
                        <p>Tél: {{ config('app.company_phone', '+33 1 23 45 67 89') }}</p>
                        @if(config('app.company_siret'))
                        <p>SIRET: {{ config('app.company_siret') }}</p>
                        @endif
                        @if(config('app.company_vat'))
                        <p>TVA: {{ config('app.company_vat') }}</p>
                        @endif
                    </div>
                </div>
                <div class="header-col right">
                    <div class="invoice-info">
                        <h2>FACTURE</h2>
                        <p class="invoice-number">{{ $invoice->number }}</p>
                        <p>Date: {{ $invoice->issued_at->format('d/m/Y') }}</p>
                        <p>Commande: {{ $order->number }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adresses -->
        <div class="addresses">
            <div class="address-block" style="margin-right: 4%;">
                <h3>Adresse de facturation</h3>
                <p><strong>{{ $order->billing_address['firstname'] ?? '' }} {{ $order->billing_address['lastname'] ?? '' }}</strong></p>
                @if(!empty($order->billing_address['company']))
                <p>{{ $order->billing_address['company'] }}</p>
                @endif
                <p>{{ $order->billing_address['address'] ?? '' }}</p>
                @if(!empty($order->billing_address['address_complement']))
                <p>{{ $order->billing_address['address_complement'] }}</p>
                @endif
                <p>{{ $order->billing_address['postal_code'] ?? '' }} {{ $order->billing_address['city'] ?? '' }}</p>
                <p>{{ $order->billing_address['country'] ?? '' }}</p>
                @if(!empty($order->billing_address['phone']))
                <p>Tél: {{ $order->billing_address['phone'] }}</p>
                @endif
            </div>

            <div class="address-block">
                <h3>Adresse de livraison</h3>
                <p><strong>{{ $order->shipping_address['firstname'] ?? '' }} {{ $order->shipping_address['lastname'] ?? '' }}</strong></p>
                @if(!empty($order->shipping_address['company']))
                <p>{{ $order->shipping_address['company'] }}</p>
                @endif
                <p>{{ $order->shipping_address['address'] ?? '' }}</p>
                @if(!empty($order->shipping_address['address_complement']))
                <p>{{ $order->shipping_address['address_complement'] }}</p>
                @endif
                <p>{{ $order->shipping_address['postal_code'] ?? '' }} {{ $order->shipping_address['city'] ?? '' }}</p>
                <p>{{ $order->shipping_address['country'] ?? '' }}</p>
                @if(!empty($order->shipping_address['phone']))
                <p>Tél: {{ $order->shipping_address['phone'] }}</p>
                @endif
            </div>
        </div>

        <!-- Articles -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="center" style="width: 10%;">SKU</th>
                    <th class="center" style="width: 10%;">Qté</th>
                    <th class="right" style="width: 15%;">Prix unitaire</th>
                    <th class="right" style="width: 15%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        <div class="product-name">{{ $item->name }}</div>
                        @if($item->sku)
                        <div class="product-sku">SKU: {{ $item->sku }}</div>
                        @endif
                    </td>
                    <td class="center">{{ $item->sku ?? '-' }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format($item->unit_price, 2, ',', ' ') }} €</td>
                    <td class="right">{{ number_format($item->total_price, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totaux -->
        <div class="totals">
            <div class="totals-row">
                <div class="totals-label">Sous-total:</div>
                <div class="totals-value">{{ number_format($order->subtotal, 2, ',', ' ') }} €</div>
            </div>

            @if($order->discount_total > 0)
            <div class="totals-row">
                <div class="totals-label">Remise:</div>
                <div class="totals-value" style="color: #dc2626;">-{{ number_format($order->discount_total, 2, ',', ' ') }} €</div>
            </div>
            @endif

            <div class="totals-row">
                <div class="totals-label">Livraison:</div>
                <div class="totals-value">{{ number_format($order->shipping_total, 2, ',', ' ') }} €</div>
            </div>

            @if($order->tax_total > 0)
            <div class="totals-row">
                <div class="totals-label">TVA:</div>
                <div class="totals-value">{{ number_format($order->tax_total, 2, ',', ' ') }} €</div>
            </div>
            @endif

            <div class="totals-row total">
                <div class="totals-label">TOTAL TTC:</div>
                <div class="totals-value">{{ number_format($order->total, 2, ',', ' ') }} €</div>
            </div>
        </div>

        <!-- Informations de paiement -->
        @if($order->payment_status === 'paid')
        <div class="payment-info">
            <h3>✓ Paiement reçu</h3>
            <p>Cette facture a été réglée le {{ $order->placed_at ? $order->placed_at->format('d/m/Y') : 'N/A' }}</p>
            <p>Mode de paiement: Carte bancaire</p>
        </div>
        @endif

        <!-- Pied de page -->
        <div class="footer">
            <p>{{ config('app.name', 'Omersia') }} - Merci pour votre confiance !</p>
            @if(config('app.company_legal'))
            <p>{{ config('app.company_legal') }}</p>
            @endif
        </div>
    </div>
</body>
</html>
