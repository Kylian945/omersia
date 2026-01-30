@extends('emails.layout')

@section('content')
    <div class="greeting">
        Bonjour {{ $customer->firstname ?? 'Client' }} !
    </div>

    <div class="content">
        <p style="font-size: 16px; font-weight: 500;">Bienvenue chez {{ config('app.name', 'Omersia') }} ! 🎉</p>
        <p>Nous sommes ravis de vous compter parmi nos clients. Votre compte a été créé avec succès.</p>
    </div>

    <div class="info-box">
        <div class="info-box-title">Votre compte</div>
        <div class="info-box-content">
            <table style="width: 100%; border: none; margin: 0;">
                <tr>
                    <td style="border: none; padding: 5px 0;">Email :</td>
                    <td style="border: none; padding: 5px 0; text-align: right; font-weight: 600;">{{ $customer->email }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 5px 0;">Nom :</td>
                    <td style="border: none; padding: 5px 0; text-align: right;">{{ $customer->fullname }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="content">
        <p><strong>Avec votre compte, vous pouvez :</strong></p>
        <ul style="line-height: 1.8; color: #333;">
            <li>Suivre vos commandes en temps réel</li>
            <li>Enregistrer plusieurs adresses de livraison</li>
            <li>Consulter l'historique de vos achats</li>
            <li>Accéder à des offres exclusives</li>
            <li>Gérer vos préférences et informations personnelles</li>
        </ul>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.frontend_url') }}/account" class="button">
            Accéder à mon compte
        </a>
    </div>

    <div class="divider"></div>

    <div class="content">
        <p><strong>Besoin d'aide ?</strong></p>
        <p style="color: #666; font-size: 13px;">
            Notre équipe est à votre disposition pour répondre à toutes vos questions. N'hésitez pas à nous contacter !
        </p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.frontend_url') }}/shop" class="button" style="background-color: #666;">
            Découvrir nos produits
        </a>
    </div>

    <div class="content" style="margin-top: 30px;">
        <p style="color: #666; font-size: 13px;">
            À très bientôt !<br>
            L'équipe {{ config('app.name', 'Omersia') }}
        </p>
    </div>
@endsection
