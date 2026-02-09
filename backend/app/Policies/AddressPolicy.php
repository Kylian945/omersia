<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Omersia\Customer\Models\Address;
use Omersia\Customer\Models\Customer;

/**
 * SEC-001: Policy pour sécuriser l'accès aux adresses
 * Empêche les IDOR (Insecure Direct Object Reference)
 */
class AddressPolicy
{
    use HandlesAuthorization;

    /**
     * Le client peut voir ses propres adresses
     */
    public function view(Customer $customer, Address $address): bool
    {
        return $address->customer_id === $customer->id;
    }

    /**
     * Le client peut créer une adresse pour lui-même
     */
    public function create(Customer $customer): bool
    {
        return true;
    }

    /**
     * Le client peut modifier ses propres adresses
     */
    public function update(Customer $customer, Address $address): bool
    {
        return $address->customer_id === $customer->id;
    }

    /**
     * Le client peut supprimer ses propres adresses
     */
    public function delete(Customer $customer, Address $address): bool
    {
        return $address->customer_id === $customer->id;
    }

    /**
     * Le client peut définir ses propres adresses par défaut
     */
    public function setDefault(Customer $customer, Address $address): bool
    {
        return $address->customer_id === $customer->id;
    }
}
