<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Services;

use Illuminate\Support\Facades\Storage;
use Omersia\Customer\Models\Customer;
use Omersia\Gdpr\Models\DataRequest;

/**
 * Service pour exporter les données personnelles (DSAR - Data Subject Access Request)
 */
class DataExportService
{
    /**
     * Exporter toutes les données d'un customer au format JSON
     */
    public function exportCustomerData(Customer $customer): array
    {
        return [
            'personal_information' => $this->exportPersonalInformation($customer),
            'addresses' => $this->exportAddresses($customer),
            'orders' => $this->exportOrders($customer),
            'cart' => $this->exportCart($customer),
            'cookie_consents' => $this->exportCookieConsents($customer),
            'data_requests' => $this->exportDataRequests($customer),
            'export_date' => now()->toIso8601String(),
            'export_format' => 'JSON',
        ];
    }

    /**
     * Générer un fichier d'export et le sauvegarder
     */
    public function generateExportFile(DataRequest $dataRequest): string
    {
        $customer = $dataRequest->customer;
        $data = $this->exportCustomerData($customer);

        // Générer un nom de fichier unique
        $filename = sprintf(
            'gdpr_export_%s_%s.json',
            $customer->id,
            now()->format('Y-m-d_His')
        );

        $path = "gdpr/exports/{$filename}";

        // Sauvegarder le fichier (storage privé)
        Storage::disk('local')->put(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // Mettre à jour la demande avec le chemin du fichier
        $dataRequest->update([
            'export_file_path' => $path,
            'export_expires_at' => now()->addHours(72), // 72h pour télécharger (RGPD)
        ]);

        return $path;
    }

    /**
     * Obtenir le contenu du fichier d'export
     */
    public function getExportFileContent(string $path): ?string
    {
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        return Storage::disk('local')->get($path);
    }

    /**
     * Supprimer un fichier d'export expiré
     */
    public function deleteExpiredExportFile(string $path): bool
    {
        return Storage::disk('local')->delete($path);
    }

    /**
     * Nettoyer tous les fichiers d'export expirés
     */
    public function cleanExpiredExports(): int
    {
        $expiredRequests = DataRequest::whereNotNull('export_file_path')
            ->where('export_expires_at', '<', now())
            ->get();

        $deleted = 0;
        foreach ($expiredRequests as $request) {
            if ($this->deleteExpiredExportFile($request->export_file_path)) {
                $request->update(['export_file_path' => null]);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Exporter les informations personnelles
     */
    private function exportPersonalInformation(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'email' => $customer->email,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'phone' => $customer->phone,
            'date_of_birth' => $customer->date_of_birth?->toDateString(),
            'created_at' => $customer->created_at->toIso8601String(),
            'updated_at' => $customer->updated_at->toIso8601String(),
        ];
    }

    /**
     * Exporter les adresses
     */
    private function exportAddresses(Customer $customer): array
    {
        return $customer->addresses->map(fn ($address) => [
            'type' => $address->type,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'postcode' => $address->postcode,
            'city' => $address->city,
            'country' => $address->country,
            'phone' => $address->phone,
            'is_default_shipping' => $address->is_default_shipping,
            'is_default_billing' => $address->is_default_billing,
        ])->toArray();
    }

    /**
     * Exporter les commandes
     */
    private function exportOrders(Customer $customer): array
    {
        return $customer->orders->map(fn ($order) => [
            'number' => $order->number,
            'status' => $order->status,
            'total' => $order->total,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
            ])->toArray(),
        ])->toArray();
    }

    /**
     * Exporter le panier actif
     */
    private function exportCart(Customer $customer): ?array
    {
        $cart = $customer->carts()->where('status', 'active')->first();

        if (! $cart) {
            return null;
        }

        return [
            'items' => $cart->items->map(fn ($item) => [
                'product_name' => $item->product?->translation()?->name,
                'quantity' => $item->qty,
                'price' => $item->unit_price,
            ])->toArray(),
        ];
    }

    /**
     * Exporter l'historique des consentements cookies
     */
    private function exportCookieConsents(Customer $customer): array
    {
        return $customer->cookieConsents->map(fn ($consent) => [
            'consented_at' => $consent->consented_at->toIso8601String(),
            'functional' => $consent->functional,
            'analytics' => $consent->analytics,
            'marketing' => $consent->marketing,
            'ip_address' => $consent->ip_address,
        ])->toArray();
    }

    /**
     * Exporter l'historique des demandes RGPD
     */
    private function exportDataRequests(Customer $customer): array
    {
        return $customer->dataRequests->map(fn ($request) => [
            'type' => $request->type,
            'status' => $request->status,
            'requested_at' => $request->requested_at->toIso8601String(),
            'completed_at' => $request->completed_at?->toIso8601String(),
        ])->toArray();
    }
}
