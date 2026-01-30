<?php

declare(strict_types=1);

namespace Omersia\Api\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Omersia\Api\DTO\OrderCreateDTO;
use Omersia\Api\DTO\OrderUpdateDTO;
use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\ShippingMethod;
use Omersia\Catalog\Services\SequenceService;

/**
 * Service pour la création et mise à jour de commandes
 */
class OrderCreationService
{
    public function __construct(
        private readonly OrderItemService $orderItemService,
        private readonly SequenceService $sequenceService,
        private readonly OrderPriceValidationService $priceValidationService
    ) {}

    /**
     * Créer ou mettre à jour une commande en mode draft
     * Si une commande draft existe déjà pour ce panier, elle sera mise à jour
     */
    public function createOrUpdateDraftOrder(OrderCreateDTO $dto, ShippingMethod $shippingMethod): Order
    {
        return DB::transaction(function () use ($dto, $shippingMethod) {
            // SÉCURITÉ DCA-012: Valider les prix côté serveur
            $validationResult = $this->priceValidationService->validateAndRecalculate($dto);

            // Créer un DTO avec les prix vérifiés
            $verifiedDto = $dto->withVerifiedPrices(
                verifiedItems: $validationResult['verified_items'],
                verifiedSubtotal: $validationResult['verified_subtotal'],
                verifiedDiscountTotal: $validationResult['verified_discount_total'],
                discountIds: $validationResult['discount_ids']
            );

            // Chercher une commande draft existante pour ce panier
            $order = null;
            if ($verifiedDto->cartId) {
                $order = Order::where('cart_id', $verifiedDto->cartId)
                    ->where('customer_id', $verifiedDto->customerId)
                    ->where('status', 'draft')
                    ->first();
            }

            if ($order) {
                // Mettre à jour la commande existante
                $this->updateExistingDraftOrder($order, $verifiedDto, $shippingMethod, $validationResult['discount_ids']);
            } else {
                // Créer une nouvelle commande
                $order = $this->createNewDraftOrder($verifiedDto, $shippingMethod, $validationResult['discount_ids']);
            }

            // Synchroniser les items
            $this->orderItemService->syncItems($order, $verifiedDto->items);

            // DCA-014: Logger la création/mise à jour de commande
            Log::channel('transactions')->info('Order draft created/updated', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'customer_id' => $verifiedDto->customerId,
                'customer_email' => $verifiedDto->customerEmail,
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'shipping_total' => $order->shipping_total,
                'tax_total' => $order->tax_total,
                'total' => $order->total,
                'currency' => $order->currency,
                'items_count' => count($verifiedDto->items),
                'cart_id' => $verifiedDto->cartId,
                'action' => $order->wasRecentlyCreated ? 'created' : 'updated',
            ]);

            return $order->fresh(['items', 'shippingMethod']);
        });
    }

    /**
     * Mettre à jour une commande existante
     */
    public function updateOrder(Order $order, OrderUpdateDTO $dto): Order
    {
        return DB::transaction(function () use ($order, $dto) {
            // Mettre à jour les champs principaux
            $order->currency = $dto->currency;
            $order->shipping_method_id = $dto->shippingMethodId;
            $order->customer_email = $dto->customerEmail;
            $order->customer_firstname = $dto->customerFirstname;
            $order->customer_lastname = $dto->customerLastname;
            $order->shipping_address = $dto->shippingAddress;
            $order->billing_address = $dto->billingAddress;
            $order->discount_total = $dto->discountTotal;
            $order->shipping_total = $dto->shippingTotal;
            $order->tax_total = $dto->taxTotal;
            $order->total = $dto->total;
            $order->save();

            // Synchroniser les items
            $this->orderItemService->syncItems($order, $dto->items);

            return $order->fresh(['items']);
        });
    }

    /**
     * Mettre à jour une commande draft existante
     */
    private function updateExistingDraftOrder(Order $order, OrderCreateDTO $dto, ShippingMethod $shippingMethod, array $discountIds): void
    {
        $subtotal = $dto->calculateSubtotal();
        $total = $dto->calculateTotal();

        $order->shipping_method_id = $shippingMethod->id;
        $order->currency = $dto->currency;
        $order->subtotal = $subtotal;
        $order->discount_total = $dto->discountTotal;
        $order->shipping_total = $dto->shippingTotal;
        $order->tax_total = $dto->taxTotal;
        $order->total = $total;
        $order->customer_email = $dto->customerEmail;
        $order->customer_firstname = $dto->customerFirstname;
        $order->customer_lastname = $dto->customerLastname;
        $order->shipping_address = $dto->shippingAddress;
        $order->billing_address = $dto->billingAddress;
        $order->applied_discounts = $discountIds;
        $order->save();

        // Enregistrer l'utilisation des réductions
        $order->recordDiscountUsage($discountIds);
    }

    /**
     * Créer une nouvelle commande en mode draft
     */
    private function createNewDraftOrder(OrderCreateDTO $dto, ShippingMethod $shippingMethod, array $discountIds): Order
    {
        $subtotal = $dto->calculateSubtotal();
        $total = $dto->calculateTotal();

        $order = Order::create([
            'cart_id' => $dto->cartId,
            'customer_id' => $dto->customerId,
            'shipping_method_id' => $shippingMethod->id,
            'currency' => $dto->currency,
            'status' => 'draft',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'subtotal' => $subtotal,
            'discount_total' => $dto->discountTotal,
            'shipping_total' => $dto->shippingTotal,
            'tax_total' => $dto->taxTotal,
            'total' => $total,
            'customer_email' => $dto->customerEmail,
            'customer_firstname' => $dto->customerFirstname,
            'customer_lastname' => $dto->customerLastname,
            'shipping_address' => $dto->shippingAddress,
            'billing_address' => $dto->billingAddress,
            'applied_discounts' => $discountIds,
            'placed_at' => now(),
        ]);

        // Générer le numéro de commande atomiquement
        $order->number = $this->sequenceService->next('order_number', 'ORD-');
        $order->save();

        // Enregistrer l'utilisation des réductions
        $order->recordDiscountUsage($discountIds);

        return $order;
    }
}
