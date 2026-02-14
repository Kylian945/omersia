<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Services;

use Illuminate\Support\Facades\DB;
use Omersia\Customer\Models\Customer;
use Omersia\Gdpr\Models\DataDeletionLog;
use Omersia\Gdpr\Models\DataRequest;

/**
 * Service pour supprimer les données personnelles (Droit à l'oubli RGPD)
 */
class DataDeletionService
{
    /**
     * Supprimer toutes les données d'un customer (soft delete + anonymisation)
     */
    public function deleteCustomerData(
        Customer $customer,
        DataRequest $dataRequest,
        int $deletedByUserId,
        string $method = 'full_deletion'
    ): DataDeletionLog {
        return DB::transaction(function () use ($customer, $dataRequest, $deletedByUserId, $method) {
            $deletedTables = [];
            $anonymizedTables = [];
            $totalDeleted = 0;
            $totalAnonymized = 0;

            // 1. Anonymiser les commandes (on garde l'historique pour la comptabilité)
            $ordersCount = $this->anonymizeOrders($customer);
            if ($ordersCount > 0) {
                $anonymizedTables[] = 'orders';
                $totalAnonymized += $ordersCount;
            }

            // 2. Supprimer les paniers
            $cartsCount = $customer->carts()->delete();
            if ($cartsCount > 0) {
                $deletedTables[] = 'carts';
                $totalDeleted += $cartsCount;
            }

            // 3. Supprimer les adresses
            $addressesCount = $customer->addresses()->delete();
            if ($addressesCount > 0) {
                $deletedTables[] = 'addresses';
                $totalDeleted += $addressesCount;
            }

            // 4. Supprimer les consentements cookies
            $consentsCount = $customer->cookieConsents()->delete();
            if ($consentsCount > 0) {
                $deletedTables[] = 'cookie_consents';
                $totalDeleted += $consentsCount;
            }

            // 5. Marquer les demandes RGPD comme traitées
            $customer->dataRequests()
                ->whereNot('id', $dataRequest->id)
                ->update(['data_deleted' => true]);

            // 6. Anonymiser le compte customer (on ne supprime pas pour garder les relations)
            $this->anonymizeCustomer($customer);
            $anonymizedTables[] = 'customers';
            $totalAnonymized++;

            // 7. Créer le log de suppression
            $log = DataDeletionLog::create([
                'customer_id' => $customer->id,
                'customer_email' => $customer->getOriginal('email'), // Email avant anonymisation
                'data_request_id' => $dataRequest->id,
                'deleted_tables' => $deletedTables,
                'anonymized_tables' => $anonymizedTables,
                'total_records_deleted' => $totalDeleted,
                'total_records_anonymized' => $totalAnonymized,
                'deleted_by' => $deletedByUserId,
                'deleted_at' => now(),
                'deletion_method' => $method,
            ]);

            // 8. Mettre à jour la demande
            $dataRequest->update([
                'data_deleted' => true,
                'deleted_data_summary' => [
                    'deleted_tables' => $deletedTables,
                    'anonymized_tables' => $anonymizedTables,
                    'total_deleted' => $totalDeleted,
                    'total_anonymized' => $totalAnonymized,
                ],
            ]);

            return $log;
        });
    }

    /**
     * Anonymiser les commandes (on garde pour la comptabilité mais on anonymise les données perso)
     */
    private function anonymizeOrders(Customer $customer): int
    {
        return $customer->orders()->update([
            'customer_email' => 'deleted@example.com',
            'customer_firstname' => 'Deleted',
            'customer_lastname' => 'User',
            'shipping_address' => json_encode([
                'line1' => 'Deleted',
                'postcode' => '00000',
                'city' => 'Deleted',
                'country' => 'XX',
            ]),
            'billing_address' => json_encode([
                'line1' => 'Deleted',
                'postcode' => '00000',
                'city' => 'Deleted',
                'country' => 'XX',
            ]),
        ]);
    }

    /**
     * Anonymiser le compte customer
     */
    private function anonymizeCustomer(Customer $customer): void
    {
        $customer->update([
            'email' => 'deleted_'.$customer->id.'@deleted.local',
            'firstname' => 'Deleted',
            'lastname' => 'User',
            'phone' => null,
            'date_of_birth' => null,
            'password' => bcrypt(str()->random(32)), // Mot de passe aléatoire
        ]);

        $customer->delete();
    }

    /**
     * Vérifier si un customer peut être supprimé
     */
    public function canDeleteCustomer(Customer $customer): array
    {
        $canDelete = true;
        $reasons = [];

        // Vérifier s'il y a des commandes en cours
        $pendingOrders = $customer->orders()
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        if ($pendingOrders > 0) {
            $canDelete = false;
            $reasons[] = "Le client a {$pendingOrders} commande(s) en cours de traitement.";
        }

        // Vérifier s'il y a des demandes RGPD en cours
        $pendingRequests = $customer->dataRequests()
            ->whereIn('status', ['pending', 'processing'])
            ->where('type', '!=', 'deletion')
            ->count();

        if ($pendingRequests > 0) {
            $canDelete = false;
            $reasons[] = "Le client a {$pendingRequests} demande(s) RGPD en cours.";
        }

        return [
            'can_delete' => $canDelete,
            'reasons' => $reasons,
        ];
    }
}
