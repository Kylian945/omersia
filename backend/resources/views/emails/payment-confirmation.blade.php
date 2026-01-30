@extends('emails.layout')

@section('content')
    <div class="greeting">
        Bonjour {{ $customer->firstname ?? 'Client' }},
    </div>

    <div class="alert alert-success">
        <strong style="color: #155724;">✓ Paiement confirmé</strong>
        <p style="margin: 10px 0 0 0; color: #155724;">Nous avons bien reçu votre paiement pour la commande #{{ $order->number }}.</p>
    </div>

    <div class="content">
        <p>Votre paiement a été traité avec succès. Nous préparons maintenant votre commande pour l'expédition.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0;">
        <div class="info-box">
            <div class="info-box-title">Montant payé</div>
            <div class="info-box-content" style="font-size: 20px; font-weight: 600; color: #28a745;">
                {{ number_format(($payment->amount/100), 2, ',', ' ') }} {{ $payment->currency }}
            </div>
        </div>

        <div class="info-box">
            <div class="info-box-title">Méthode de paiement</div>
            <div class="info-box-content">
                {{ $payment->provider->name ?? 'Carte bancaire' }}
                @if($payment->provider_payment_id)
                    <br><span style="font-size: 11px; color: #999;">ID: {{ $payment->provider_payment_id }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="info-box">
        <div class="info-box-title">Récapitulatif de la commande</div>
        <div class="info-box-content">
            <table style="width: 100%; border: none; margin: 0;">
                <tr>
                    <td style="border: none; padding: 5px 0;">Numéro de commande :</td>
                    <td style="border: none; padding: 5px 0; text-align: right; font-weight: 600;">#{{ $order->number }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 5px 0;">Date de paiement :</td>
                    <td style="border: none; padding: 5px 0; text-align: right;">{{ $payment->created_at->format('d/m/Y à H:i') }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 5px 0;">Statut :</td>
                    <td style="border: none; padding: 5px 0; text-align: right; color: #28a745; font-weight: 600;">Payé</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="divider"></div>

    <div class="content">
        <p>Vous recevrez une notification dès que votre commande sera expédiée.</p>
        <p>Un récapitulatif détaillé de votre commande vous a été envoyé séparément.</p>
    </div>

    <div style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/account/orders/{{ $order->id }}" class="button">
            Voir ma commande
        </a>
    </div>

    <div class="content" style="margin-top: 30px;">
        <p style="color: #666; font-size: 13px;">
            Merci pour votre achat !<br>
            L'équipe {{ config('app.name', 'Omersia') }}
        </p>
    </div>
@endsection
