@extends('emails.layout')

@section('content')
    <div class="greeting">
        Bonjour {{ $customer->firstname ?? 'Client' }},
    </div>

    <div class="content">
        <p>Merci pour votre commande ! Nous avons bien reçu votre commande et nous la préparons avec soin.</p>
        <p>Voici le récapitulatif de votre commande :</p>
    </div>

    <div class="info-box">
        <div class="info-box-title">Numéro de commande</div>
        <div class="info-box-content" style="font-size: 16px; font-weight: 600; color: #000;">
            #{{ $order->number }}
        </div>
    </div>

    <h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 16px;">Articles commandés</h3>
    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th style="text-align: center;">Quantité</th>
                <th style="text-align: right;">Prix unitaire</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    <strong>{{ $item->name }}</strong>
                    @if($item->sku)
                        <br><span style="color: #666; font-size: 12px;">SKU: {{ $item->sku }}</span>
                    @endif
                </td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">{{ number_format($item->unit_price, 2, ',', ' ') }} {{ $order->currency }}</td>
                <td style="text-align: right;">{{ number_format($item->total_price, 2, ',', ' ') }} {{ $order->currency }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; border-bottom: none;">Sous-total :</td>
                <td style="text-align: right; border-bottom: none;">{{ number_format($order->subtotal, 2, ',', ' ') }} {{ $order->currency }}</td>
            </tr>
            @if($order->discount_total > 0)
            <tr>
                <td colspan="3" style="text-align: right; border-bottom: none;">Réduction :</td>
                <td style="text-align: right; border-bottom: none; color: #28a745;">-{{ number_format($order->discount_total, 2, ',', ' ') }} {{ $order->currency }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="3" style="text-align: right; border-bottom: none;">Livraison :</td>
                <td style="text-align: right; border-bottom: none;">{{ number_format($order->shipping_total, 2, ',', ' ') }} {{ $order->currency }}</td>
            </tr>
            @if($order->tax_total > 0)
            <tr>
                <td colspan="3" style="text-align: right; border-bottom: none;">Taxes :</td>
                <td style="text-align: right; border-bottom: none;">{{ number_format($order->tax_total, 2, ',', ' ') }} {{ $order->currency }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">Total :</td>
                <td style="text-align: right;">{{ number_format($order->total, 2, ',', ' ') }} {{ $order->currency }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
        <div class="info-box">
            <div class="info-box-title">Adresse de livraison</div>
            <div class="info-box-content">
                @if(is_array($shippingAddress))
                    {{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}<br>
                    @if(!empty($shippingAddress['company']))
                        {{ $shippingAddress['company'] }}<br>
                    @endif
                    {{ $shippingAddress['line1'] ?? '' }}<br>
                    @if(!empty($shippingAddress['line2']))
                        {{ $shippingAddress['line2'] }}<br>
                    @endif
                    {{ $shippingAddress['postcode'] ?? '' }} {{ $shippingAddress['city'] ?? '' }}<br>
                    {{ $shippingAddress['country'] ?? '' }}
                    @if(!empty($shippingAddress['phone']))
                        <br>Tél: {{ $shippingAddress['phone'] }}
                    @endif
                @endif
            </div>
        </div>

        <div class="info-box">
            <div class="info-box-title">Adresse de facturation</div>
            <div class="info-box-content">
                @if(is_array($billingAddress))
                    {{ $billingAddress['first_name'] ?? '' }} {{ $billingAddress['last_name'] ?? '' }}<br>
                    @if(!empty($billingAddress['company']))
                        {{ $billingAddress['company'] }}<br>
                    @endif
                    {{ $billingAddress['line1'] ?? '' }}<br>
                    @if(!empty($billingAddress['line2']))
                        {{ $billingAddress['line2'] }}<br>
                    @endif
                    {{ $billingAddress['postcode'] ?? '' }} {{ $billingAddress['city'] ?? '' }}<br>
                    {{ $billingAddress['country'] ?? '' }}
                @endif
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <div class="content">
        <p>Vous recevrez une notification par email dès que votre commande sera expédiée.</p>
        <p>Si vous avez des questions concernant votre commande, n'hésitez pas à nous contacter.</p>
    </div>

    <div style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/account/orders/{{ $order->id }}" class="button">
            Suivre ma commande
        </a>
    </div>

    <div class="content" style="margin-top: 30px;">
        <p style="color: #666; font-size: 13px;">
            Merci de votre confiance !<br>
            L'équipe {{ config('app.name', 'Omersia') }}
        </p>
    </div>
@endsection
