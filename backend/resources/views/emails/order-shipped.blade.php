@extends('emails.layout')

@section('content')
    <div class="greeting">
        Bonjour {{ $customer->firstname ?? 'Client' }},
    </div>

    <div class="alert alert-success">
        <strong style="color: #155724;">📦 Votre commande a été expédiée !</strong>
        <p style="margin: 10px 0 0 0; color: #155724;">Votre colis est en route vers vous.</p>
    </div>

    <div class="content">
        <p>Bonne nouvelle ! Votre commande #{{ $order->number }} a été expédiée et sera bientôt chez vous.</p>
    </div>

    @if($trackingNumber || $carrier)
    <div class="info-box">
        <div class="info-box-title">Informations de suivi</div>
        <div class="info-box-content">
            <table style="width: 100%; border: none; margin: 0;">
                @if($carrier)
                <tr>
                    <td style="border: none; padding: 5px 0;">Transporteur :</td>
                    <td style="border: none; padding: 5px 0; text-align: right; font-weight: 600;">{{ $carrier }}</td>
                </tr>
                @endif
                @if($trackingNumber)
                <tr>
                    <td style="border: none; padding: 5px 0;">Numéro de suivi :</td>
                    <td style="border: none; padding: 5px 0; text-align: right; font-weight: 600;">{{ $trackingNumber }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
    @endif

    @if($trackingUrl)
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $trackingUrl }}" class="button">
            Suivre mon colis
        </a>
    </div>
    @endif

    <h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 16px;">Articles expédiés</h3>
    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th style="text-align: center;">Quantité</th>
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
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="info-box" style="margin-top: 30px;">
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

    <div class="divider"></div>

    <div class="content">
        <p><strong>Conseils pour la réception :</strong></p>
        <ul style="line-height: 1.8; color: #666; font-size: 13px;">
            <li>Assurez-vous qu'une personne soit présente à l'adresse de livraison</li>
            <li>Une pièce d'identité pourra vous être demandée</li>
            <li>En cas d'absence, le transporteur laissera un avis de passage</li>
        </ul>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.frontend_url') }}/account/orders/{{ $order->id }}" class="button" style="background-color: #666;">
            Voir ma commande
        </a>
    </div>

    <div class="content" style="margin-top: 30px;">
        <p style="color: #666; font-size: 13px;">
            Merci pour votre commande !<br>
            L'équipe {{ config('app.name', 'Omersia') }}
        </p>
    </div>
@endsection
