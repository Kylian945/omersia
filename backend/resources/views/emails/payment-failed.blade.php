@extends('emails.layout')

@section('content')
    <div class="greeting">
        Bonjour {{ $customer->firstname ?? 'Client' }},
    </div>

    <div class="alert alert-error">
        <strong style="color: #721c24;">⚠ Échec du paiement</strong>
        <p style="margin: 10px 0 0 0; color: #721c24;">Nous n'avons pas pu traiter le paiement de votre commande #{{ $order->number }}.</p>
    </div>

    <div class="content">
        <p>Malheureusement, votre paiement n'a pas pu être validé.</p>

        @if($reason)
        <div class="info-box" style="background-color: #fff; border-color: #ffc107;">
            <div class="info-box-title" style="color: #856404;">Raison du refus</div>
            <div class="info-box-content" style="color: #856404;">
                {{ $reason }}
            </div>
        </div>
        @endif

        <p><strong>Que faire maintenant ?</strong></p>
        <ul style="line-height: 1.8; color: #333;">
            <li>Vérifiez que les informations de votre carte bancaire sont correctes</li>
            <li>Assurez-vous d'avoir des fonds suffisants sur votre compte</li>
            <li>Contactez votre banque pour plus d'informations</li>
            <li>Essayez avec un autre moyen de paiement</li>
        </ul>
    </div>

    <div class="info-box">
        <div class="info-box-title">Détails de la commande</div>
        <div class="info-box-content">
            <table style="width: 100%; border: none; margin: 0;">
                <tr>
                    <td style="border: none; padding: 5px 0;">Numéro de commande :</td>
                    <td style="border: none; padding: 5px 0; text-align: right; font-weight: 600;">#{{ $order->number }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 5px 0;">Montant :</td>
                    <td style="border: none; padding: 5px 0; text-align: right; font-weight: 600;">{{ number_format($order->total, 2, ',', ' ') }} {{ $order->currency }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 5px 0;">Statut :</td>
                    <td style="border: none; padding: 5px 0; text-align: right; color: #dc3545; font-weight: 600;">Paiement échoué</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="content">
        <p>Votre commande est toujours réservée. Cliquez sur le bouton ci-dessous pour réessayer le paiement :</p>
    </div>

    <div style="text-align: center;">
        @if($retryUrl)
        <a href="{{ $retryUrl }}" class="button" style="background-color: #dc3545;">
            Réessayer le paiement
        </a>
        @else
        <a href="{{ config('app.frontend_url') }}/checkout/payment?order={{ $order->id }}" class="button" style="background-color: #dc3545;">
            Réessayer le paiement
        </a>
        @endif
    </div>

    <div class="divider"></div>

    <div class="content">
        <p style="color: #666; font-size: 13px;">
            <strong>Besoin d'aide ?</strong><br>
            Si vous continuez à rencontrer des difficultés, n'hésitez pas à nous contacter. Nous sommes là pour vous aider !
        </p>
    </div>

    <div class="content" style="margin-top: 30px;">
        <p style="color: #666; font-size: 13px;">
            L'équipe {{ config('app.name', 'Omersia') }}
        </p>
    </div>
@endsection
