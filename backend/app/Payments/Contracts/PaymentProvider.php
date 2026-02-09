<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use Illuminate\Http\Request;
use Omersia\Catalog\Models\Order;

interface PaymentProvider
{
    /**
     * Crée un paiement pour une commande.
     * Retourne les infos nécessaires au front (client_secret, redirect_url, etc.)
     */
    public function createPaymentIntent(Order $order, array $options = []): array;

    /**
     * Traite un webhook provider → doit mettre à jour la commande & le Payment.
     */
    public function handleWebhook(Request $request): void;
}
