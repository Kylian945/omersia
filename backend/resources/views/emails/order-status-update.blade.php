@extends('emails.layout')

@section('content')
    <div class="greeting">
        Bonjour {{ $customer->firstname ?? 'Client' }},
    </div>

    @if($statusType === 'delivered')
        <div class="alert alert-success">
            <strong style="color: #155724;">✓ Votre commande a été livrée !</strong>
            <p style="margin: 10px 0 0 0; color: #155724;">Votre colis a été livré avec succès.</p>
        </div>

        <div class="content">
            <p>Nous espérons que votre commande #{{ $order->number }} vous apporte entière satisfaction.</p>
            @if($statusMessage)
                <p>{{ $statusMessage }}</p>
            @endif
        </div>

        <div class="info-box">
            <div class="info-box-title">Adresse de livraison</div>
            <div class="info-box-content">
                @if(is_array($shippingAddress))
                    {{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}<br>
                    {{ $shippingAddress['line1'] ?? '' }}<br>
                    @if(!empty($shippingAddress['line2']))
                        {{ $shippingAddress['line2'] }}<br>
                    @endif
                    {{ $shippingAddress['postcode'] ?? '' }} {{ $shippingAddress['city'] ?? '' }}<br>
                    {{ $shippingAddress['country'] ?? '' }}
                @endif
            </div>
        </div>

        <div class="divider"></div>

        <div class="content">
            <p><strong>Vous avez reçu votre commande ?</strong></p>
            <p>Nous serions ravis de connaître votre avis ! Votre retour nous aide à nous améliorer.</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/account/orders/{{ $order->id }}/review" class="button">
                Donner mon avis
            </a>
        </div>

    @elseif($statusType === 'in_transit')
        <div class="alert alert-info">
            <strong style="color: #004085;">🚚 Votre colis est en cours de livraison</strong>
            <p style="margin: 10px 0 0 0; color: #004085;">Votre commande #{{ $order->number }} arrive bientôt !</p>
        </div>

        <div class="content">
            <p>Votre colis est actuellement en cours de livraison et devrait arriver très prochainement.</p>
            @if($statusMessage)
                <p>{{ $statusMessage }}</p>
            @endif
        </div>

        <div class="info-box">
            <div class="info-box-title">Adresse de livraison</div>
            <div class="info-box-content">
                @if(is_array($shippingAddress))
                    {{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}<br>
                    {{ $shippingAddress['line1'] ?? '' }}<br>
                    @if(!empty($shippingAddress['line2']))
                        {{ $shippingAddress['line2'] }}<br>
                    @endif
                    {{ $shippingAddress['postcode'] ?? '' }} {{ $shippingAddress['city'] ?? '' }}<br>
                    {{ $shippingAddress['country'] ?? '' }}
                @endif
            </div>
        </div>

        <div class="content">
            <p><strong>Assurez-vous qu'une personne soit présente à l'adresse de livraison.</strong></p>
        </div>

    @elseif($statusType === 'delivery_delayed')
        <div class="alert" style="background-color: #fff3cd; border-color: #ffc107;">
            <strong style="color: #856404;">⚠ Retard de livraison</strong>
            <p style="margin: 10px 0 0 0; color: #856404;">Votre commande #{{ $order->number }} prend un peu de retard.</p>
        </div>

        <div class="content">
            <p>Nous tenons à vous informer qu'un léger retard affecte la livraison de votre commande.</p>
            @if($statusMessage)
                <p><strong>Information du transporteur :</strong><br>{{ $statusMessage }}</p>
            @endif
            <p>Nous mettons tout en œuvre pour vous livrer dans les meilleurs délais. Nous vous remercions de votre patience et compréhension.</p>
        </div>

        <div class="info-box">
            <div class="info-box-title">Que se passe-t-il ?</div>
            <div class="info-box-content">
                <p style="margin: 0;">Les retards peuvent être causés par :</p>
                <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #666;">
                    <li>Des conditions météorologiques défavorables</li>
                    <li>Un volume de colis plus important que prévu</li>
                    <li>Des contraintes logistiques exceptionnelles</li>
                </ul>
            </div>
        </div>
    @endif

    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.frontend_url') }}/account/orders/{{ $order->id }}" class="button" style="background-color: #666;">
            Voir ma commande
        </a>
    </div>

    <div class="divider"></div>

    <div class="content">
        <p style="color: #666; font-size: 13px;">
            @if($statusType === 'delivered')
                Merci de votre confiance !<br>
            @else
                Merci pour votre patience.<br>
            @endif
            L'équipe {{ config('app.name', 'Omersia') }}
        </p>
    </div>
@endsection
