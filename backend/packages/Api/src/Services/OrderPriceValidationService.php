<?php

declare(strict_types=1);

namespace Omersia\Api\Services;

use Omersia\Api\DTO\CartItemDTO;
use Omersia\Api\DTO\DiscountApplicationDTO;
use Omersia\Api\DTO\OrderCreateDTO;
use Omersia\Api\Exceptions\PriceTamperingException;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductVariant;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Models\Discount;
use Omersia\Sales\Models\DiscountUsage;

/**
 * Service de validation des prix côté serveur.
 * Prévient les manipulations de prix (DCA-012).
 *
 * ## Flux de validation
 *
 * 1. Les prix unitaires soumis par le frontend sont comparés aux prix DB réels
 *    (tolérance d'arrondi 0,01 €). Un écart lève PriceTamperingException.
 *
 * 2. Les discounts sont recalculés *entièrement côté serveur* :
 *    - Discounts automatiques actifs → éligibilité réévaluée via DiscountEvaluationService
 *    - Codes manuels soumis → validité, dates, limites d'usage revérifiées
 *    Le montant total (`realDiscountTotal`) est la somme de tous les discounts applicables.
 *
 * 3. Si |realDiscountTotal - dto.discountTotal| > 0,01 € → PriceTamperingException (DCA-012).
 *
 * ## Comportement attendu sur un "mismatch"
 *
 * - **Inflation du discount (fraud)** : le frontend soumet discountTotal > valeur serveur.
 *   → PriceTamperingException. La commande est refusée.
 *
 * - **Discount automatique manquant côté frontend** : un nouveau discount automatique a
 *   été activé entre le chargement du panier et la confirmation. Le serveur calcule un
 *   discount que le frontend n'a pas inclus → mismatch → exception.
 *   Le frontend DOIT appeler POST /cart/apply-automatic-discounts avant de soumettre
 *   la commande pour synchroniser les valeurs.
 *
 * - **Code expiré entre la saisie et la soumission** : le serveur rejette le code
 *   (PriceTamperingException "not active" / "has expired"). Le frontend doit présenter
 *   l'erreur à l'utilisateur et recalculer le panier.
 *
 * - **Usage limit dépassé (discount automatique)** : le discount est silencieusement
 *   ignoré dans la somme serveur. Si le frontend a inclus ce montant, → mismatch
 *   → exception. Le frontend doit appeler POST /cart/apply-automatic-discounts
 *   juste avant la création de commande pour avoir les valeurs à jour.
 *
 * ## Risque résiduel connu
 *
 * `buildDiscountApplicationDTO` passe les prix du DTO soumis (non les `$validatedItems`)
 * à DiscountEvaluationService. C'est sans danger car `validateItemPrices` a déjà confirmé
 * que les prix soumis = prix DB, mais l'ordre d'appel est implicitement couplé.
 * Ne pas réordonner les étapes dans `validateAndRecalculate()` sans revérifier.
 *
 * @see DiscountEvaluationService
 * @see \Omersia\Api\Exceptions\PriceTamperingException
 */
class OrderPriceValidationService
{
    public function __construct(
        private readonly DiscountEvaluationService $discountEvaluationService
    ) {}

    /**
     * Valider et recalculer tous les prix de la commande
     *
     * @throws PriceTamperingException Si manipulation détectée
     */
    public function validateAndRecalculate(OrderCreateDTO $dto): array
    {
        // 1. Valider les prix des items avec les prix réels de la BDD
        $validatedItems = $this->validateItemPrices($dto->items);
        $realSubtotal = array_reduce(
            $validatedItems,
            fn ($sum, $item) => $sum + $item['total_price'],
            0.0
        );

        // 2. Revalider les réductions côté serveur
        $discountResult = $this->revalidateDiscounts($dto, $realSubtotal);
        $realDiscountTotal = $discountResult['total_discount'];
        $discountIds = $discountResult['discount_ids'];

        // 3. Comparer avec les valeurs soumises (tolérance 0.01€ pour arrondi)
        $tolerance = 0.01;

        if (abs($realSubtotal - $dto->calculateSubtotal()) > $tolerance) {
            throw new PriceTamperingException(
                field: 'subtotal',
                submitted: $dto->calculateSubtotal(),
                expected: $realSubtotal
            );
        }

        // DCA-012: Valider le total discount soumis contre le total recalculé serveur
        // Un mismatch indique une tentative de manipulation (ex: inflation du discount)
        if (abs($realDiscountTotal - $dto->discountTotal) > $tolerance) {
            throw new PriceTamperingException(
                field: 'discount_total',
                submitted: $dto->discountTotal,
                expected: $realDiscountTotal
            );
        }

        return [
            'verified_items' => $validatedItems,
            'verified_subtotal' => $realSubtotal,
            'verified_discount_total' => $realDiscountTotal,
            'discount_ids' => $discountIds,
        ];
    }

    /**
     * Valider les prix des items avec la BDD
     *
     * @throws PriceTamperingException Si prix item ne correspond pas
     */
    private function validateItemPrices(array $items): array
    {
        $verifiedItems = [];
        $tolerance = 0.01;

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $variantId = $item['variant_id'] ?? null;
            $submittedUnitPrice = (float) ($item['unit_price'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 1);

            // Récupérer le prix réel depuis la BDD
            if ($variantId) {
                $variant = ProductVariant::find($variantId);
                if (! $variant) {
                    throw new PriceTamperingException(
                        field: 'items.variant_id',
                        submitted: $variantId,
                        expected: 0,
                        message: "Variant ID {$variantId} not found"
                    );
                }
                $realPrice = $variant->price;

                // Vérifier le stock disponible
                if ($variant->manage_stock && $variant->stock_qty < $quantity) {
                    throw new PriceTamperingException(
                        field: 'items.stock',
                        submitted: $quantity,
                        expected: $variant->stock_qty,
                        message: "Insufficient stock for variant {$variantId}. Available: {$variant->stock_qty}, Requested: {$quantity}"
                    );
                }
            } elseif ($productId) {
                $product = Product::find($productId);
                if (! $product) {
                    throw new PriceTamperingException(
                        field: 'items.product_id',
                        submitted: $productId,
                        expected: 0,
                        message: "Product ID {$productId} not found"
                    );
                }
                $realPrice = $product->price;

                // Vérifier le stock disponible
                if ($product->manage_stock && $product->stock_qty < $quantity) {
                    throw new PriceTamperingException(
                        field: 'items.stock',
                        submitted: $quantity,
                        expected: $product->stock_qty,
                        message: "Insufficient stock for product {$productId}. Available: {$product->stock_qty}, Requested: {$quantity}"
                    );
                }
            } else {
                throw new PriceTamperingException(
                    field: 'items',
                    submitted: 0,
                    expected: 0,
                    message: 'Item must have product_id or variant_id'
                );
            }

            // Vérifier que le prix soumis correspond au prix réel
            if (abs($realPrice - $submittedUnitPrice) > $tolerance) {
                throw new PriceTamperingException(
                    field: 'items.unit_price',
                    submitted: $submittedUnitPrice,
                    expected: $realPrice
                );
            }

            $realTotalPrice = round($realPrice * $quantity, 2);

            // Créer l'item vérifié
            $verifiedItems[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'name' => $item['name'],
                'sku' => $item['sku'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $realPrice, // Prix vérifié
                'total_price' => $realTotalPrice, // Total vérifié
            ];
        }

        return $verifiedItems;
    }

    /**
     * Revalider les réductions côté serveur
     *
     * @throws PriceTamperingException Si code invalide ou usage dépassé
     */
    private function revalidateDiscounts(OrderCreateDTO $dto, float $verifiedSubtotal): array
    {
        $appliedDiscountIds = [];
        $totalDiscount = 0.0;
        $customer = Customer::find($dto->customerId);

        // 1. Charger et appliquer les réductions automatiques actives
        $automaticDiscounts = Discount::query()
            ->where('method', 'automatic')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->get();

        foreach ($automaticDiscounts as $discount) {
            // Vérifier les limites d'usage
            if (! $this->checkUsageLimits($discount, $dto->customerId)) {
                continue;
            }

            // Évaluer l'éligibilité via DiscountEvaluationService
            $applicationDTO = $this->buildDiscountApplicationDTO($dto, $discount, $customer, $verifiedSubtotal);
            $result = $this->discountEvaluationService->evaluate($applicationDTO);

            if ($result->ok) {
                $discountAmount = $result->orderDiscountAmount
                    + $result->productDiscountAmount
                    + $result->shippingDiscountAmount;

                $totalDiscount += $discountAmount;
                $appliedDiscountIds[] = $discount->id;
            }
        }

        // 2. Valider les codes de réduction manuels soumis
        foreach ($dto->appliedDiscountCodes as $code) {
            $discount = Discount::query()
                ->where('method', 'code')
                ->whereRaw('UPPER(code) = ?', [strtoupper($code)])
                ->first();

            if (! $discount) {
                throw new PriceTamperingException(
                    field: 'applied_discount_codes',
                    submitted: 0,
                    expected: 0,
                    message: "Discount code '{$code}' not found"
                );
            }

            // Vérifier si actif et dans les dates
            if (! $discount->is_active) {
                throw new PriceTamperingException(
                    field: 'applied_discount_codes',
                    submitted: 0,
                    expected: 0,
                    message: "Discount code '{$code}' is not active"
                );
            }

            if ($discount->starts_at && $discount->starts_at->isFuture()) {
                throw new PriceTamperingException(
                    field: 'applied_discount_codes',
                    submitted: 0,
                    expected: 0,
                    message: "Discount code '{$code}' is not yet valid"
                );
            }

            if ($discount->ends_at && $discount->ends_at->isPast()) {
                throw new PriceTamperingException(
                    field: 'applied_discount_codes',
                    submitted: 0,
                    expected: 0,
                    message: "Discount code '{$code}' has expired"
                );
            }

            // Vérifier les limites d'usage
            if (! $this->checkUsageLimits($discount, $dto->customerId)) {
                throw new PriceTamperingException(
                    field: 'applied_discount_codes',
                    submitted: 0,
                    expected: 0,
                    message: "Discount code '{$code}' usage limit exceeded"
                );
            }

            // Évaluer l'éligibilité
            $applicationDTO = $this->buildDiscountApplicationDTO($dto, $discount, $customer, $verifiedSubtotal);
            $result = $this->discountEvaluationService->evaluate($applicationDTO);

            if (! $result->ok) {
                throw new PriceTamperingException(
                    field: 'applied_discount_codes',
                    submitted: 0,
                    expected: 0,
                    message: "Discount code '{$code}' is not applicable: {$result->message}"
                );
            }

            $discountAmount = $result->orderDiscountAmount
                + $result->productDiscountAmount
                + $result->shippingDiscountAmount;

            $totalDiscount += $discountAmount;
            $appliedDiscountIds[] = $discount->id;
        }

        return [
            'discount_ids' => array_unique($appliedDiscountIds),
            'total_discount' => round($totalDiscount, 2),
        ];
    }

    /**
     * Vérifier les limites d'usage d'une réduction
     *
     * Note: Cette méthode doit être appelée dans une transaction
     * pour garantir l'intégrité avec lockForUpdate
     */
    private function checkUsageLimits(Discount $discount, ?int $customerId): bool
    {
        // Vérifier limite globale avec lock pour éviter race condition
        if ($discount->usage_limit) {
            $totalUsage = DiscountUsage::where('discount_id', $discount->id)
                ->lockForUpdate()
                ->sum('usage_count');

            if ($totalUsage >= $discount->usage_limit) {
                return false;
            }
        }

        // Vérifier limite par client avec lock
        if ($discount->usage_limit_per_customer && $customerId) {
            $customerUsage = DiscountUsage::where('discount_id', $discount->id)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->sum('usage_count');

            if ($customerUsage >= $discount->usage_limit_per_customer) {
                return false;
            }
        }

        return true;
    }

    /**
     * Construire un DTO pour l'évaluation de discount
     */
    private function buildDiscountApplicationDTO(
        OrderCreateDTO $orderDto,
        Discount $discount,
        ?Customer $customer,
        float $subtotal
    ): DiscountApplicationDTO {
        // Convertir les items en format attendu par DiscountEvaluationService
        $items = [];
        $productIds = [];

        foreach ($orderDto->items as $item) {
            $productId = $item['product_id'] ?? null;
            if ($productId) {
                $productIds[] = $productId;
                $items[] = new CartItemDTO(
                    id: (int) $productId,
                    price: (float) ($item['unit_price'] ?? 0),
                    qty: (int) ($item['quantity'] ?? 0),
                    variantId: isset($item['variant_id']) ? (int) $item['variant_id'] : null
                );
            }
        }

        return new DiscountApplicationDTO(
            discount: $discount,
            customer: $customer,
            subtotal: $subtotal,
            productIds: array_unique($productIds),
            items: $items
        );
    }
}
