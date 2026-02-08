<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Omersia\Catalog\Models\Order;
use Omersia\Customer\Models\Customer;

/**
 * SEC-001: Policy pour sécuriser l'accès aux commandes
 * Empêche les IDOR (Insecure Direct Object Reference)
 */
class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Le client peut voir sa propre commande
     */
    public function view(?Customer $customer, Order $order): bool
    {
        if (! $customer) {
            return false;
        }

        return $order->customer_id === $customer->id;
    }

    /**
     * Le client peut modifier sa commande seulement si elle est en draft
     */
    public function update(Customer $customer, Order $order): bool
    {
        return $order->customer_id === $customer->id && $order->isDraft();
    }

    /**
     * Le client peut confirmer sa commande si elle est en draft et payée
     */
    public function confirm(Customer $customer, Order $order): bool
    {
        return $order->customer_id === $customer->id
            && $order->isDraft()
            && $order->payment_status === 'paid';
    }

    /**
     * Seul le propriétaire peut annuler sa commande draft
     */
    public function cancel(Customer $customer, Order $order): bool
    {
        return $order->customer_id === $customer->id && $order->isDraft();
    }
}
