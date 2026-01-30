<?php

namespace Omersia\Api\Services;

use Omersia\Api\DTO\DiscountApplicationDTO;
use Omersia\Api\DTO\DiscountEvaluationResultDTO;
use Omersia\Catalog\Models\Product;

/**
 * Service pour évaluer et calculer l'application d'une réduction sur un panier
 */
class DiscountEvaluationService
{
    /**
     * Évalue si une réduction peut être appliquée au panier
     */
    public function evaluate(DiscountApplicationDTO $dto): DiscountEvaluationResultDTO
    {
        // 1) Vérifier l'éligibilité du client
        $eligibilityCheck = $this->checkCustomerEligibility($dto);
        if (! $eligibilityCheck['ok']) {
            return DiscountEvaluationResultDTO::failure($eligibilityCheck['message']);
        }

        // 2) Déterminer les produits éligibles selon le scope
        $eligibleProductIds = $this->getEligibleProductIds($dto);

        // 3) Vérifier si des produits du panier sont éligibles
        $matchingCheck = $this->checkProductMatching($dto, $eligibleProductIds);
        if (! $matchingCheck['ok']) {
            return DiscountEvaluationResultDTO::failure($matchingCheck['message']);
        }

        // 4) Vérifier les conditions de commande (montant minimum, quantité)
        $conditionsCheck = $this->checkOrderConditions($dto);
        if (! $conditionsCheck['ok']) {
            return DiscountEvaluationResultDTO::failure($conditionsCheck['message']);
        }

        // 5) Calculer les montants de réduction
        return $this->calculateDiscounts($dto, $eligibleProductIds);
    }

    /**
     * Vérifie si le client est éligible pour cette réduction
     */
    private function checkCustomerEligibility(DiscountApplicationDTO $dto): array
    {
        $discount = $dto->discount;
        $customer = $dto->customer;

        // customer_selection: all / groups / customers
        if ($discount->customer_selection === 'groups') {
            if (! $customer) {
                return [
                    'ok' => false,
                    'message' => 'Ce code est réservé à certains clients.',
                ];
            }

            $allowedGroupIds = $discount->customerGroups->pluck('id')->all();
            $customerGroupIds = $dto->getCustomerGroupIds();

            if (! array_intersect($allowedGroupIds, $customerGroupIds)) {
                return [
                    'ok' => false,
                    'message' => 'Ce code ne s\'applique pas à votre compte.',
                ];
            }
        } elseif ($discount->customer_selection === 'customers') {
            if (! $customer || ! $discount->customers->contains('id', $customer->id)) {
                return [
                    'ok' => false,
                    'message' => 'Ce code ne s\'applique pas à votre compte.',
                ];
            }
        }

        return ['ok' => true];
    }

    /**
     * Détermine les IDs produits éligibles selon le scope de la réduction
     */
    private function getEligibleProductIds(DiscountApplicationDTO $dto): array
    {
        $discount = $dto->discount;
        $productScope = $discount->product_scope ?? 'all';

        // Si 'all', tous les produits du panier sont éligibles
        if ($productScope === 'all') {
            return $dto->productIds;
        }

        // Si 'products', seuls les produits spécifiés sont éligibles
        if ($productScope === 'products') {
            return $discount->products->pluck('id')->all();
        }

        // Si 'collections', filtrer par catégories
        if ($productScope === 'collections') {
            $allowedCategoryIds = $discount->collections->pluck('id')->all();

            $products = Product::whereIn('id', $dto->productIds)
                ->with('categories')
                ->get();

            $eligibleProductIds = [];

            foreach ($products as $product) {
                $productCategoryIds = $product->categories->pluck('id')->all();
                if (array_intersect($allowedCategoryIds, $productCategoryIds)) {
                    $eligibleProductIds[] = $product->id;
                }
            }

            return $eligibleProductIds;
        }

        return [];
    }

    /**
     * Vérifie si au moins un produit du panier correspond aux critères
     */
    private function checkProductMatching(DiscountApplicationDTO $dto, array $eligibleProductIds): array
    {
        $discount = $dto->discount;
        $productScope = $discount->product_scope ?? 'all';

        if ($productScope === 'all') {
            return ['ok' => true];
        }

        $matchingProductIds = array_values(array_intersect($dto->productIds, $eligibleProductIds));

        if (empty($matchingProductIds)) {
            $message = match ($productScope) {
                'products' => "Ce code ne s'applique pas aux produits de votre panier.",
                'collections' => "Ce code ne s'applique pas aux collections présentes dans votre panier.",
                default => "Ce code ne s'applique pas aux articles de votre panier.",
            };

            return [
                'ok' => false,
                'message' => $message,
            ];
        }

        return ['ok' => true];
    }

    /**
     * Vérifie les conditions de commande (montant minimum, quantité minimum)
     */
    private function checkOrderConditions(DiscountApplicationDTO $dto): array
    {
        $discount = $dto->discount;

        // Vérifier le montant minimum
        if ($discount->min_subtotal && $dto->subtotal < $discount->min_subtotal) {
            return [
                'ok' => false,
                'message' => 'Montant minimum de commande non atteint pour ce code.',
            ];
        }

        // Vérifier la quantité minimum
        if ($discount->min_quantity) {
            $totalQuantity = $dto->getTotalQuantity();
            if ($totalQuantity < $discount->min_quantity) {
                return [
                    'ok' => false,
                    'message' => 'Quantité minimale non atteinte pour ce code.',
                ];
            }
        }

        return ['ok' => true];
    }

    /**
     * Calcule les montants de réduction selon le type
     */
    private function calculateDiscounts(DiscountApplicationDTO $dto, array $eligibleProductIds): DiscountEvaluationResultDTO
    {
        $discount = $dto->discount;

        return match ($discount->type) {
            'order' => $this->calculateOrderDiscount($dto),
            'shipping' => $this->calculateShippingDiscount($dto),
            'product' => $this->calculateProductDiscount($dto, $eligibleProductIds),
            'buy_x_get_y' => $this->calculateBuyXGetYDiscount($dto, $eligibleProductIds),
            default => DiscountEvaluationResultDTO::failure('Type de réduction non supporté.'),
        };
    }

    /**
     * Calcule la réduction de type 'order' (sur le total de la commande)
     */
    private function calculateOrderDiscount(DiscountApplicationDTO $dto): DiscountEvaluationResultDTO
    {
        $discount = $dto->discount;
        $subtotal = $dto->subtotal;

        $orderDiscountAmount = 0.0;

        if ($discount->value_type === 'percentage' && $discount->value > 0) {
            $orderDiscountAmount = round($subtotal * ($discount->value / 100), 2);
        } elseif ($discount->value_type === 'fixed_amount' && $discount->value > 0) {
            $orderDiscountAmount = min($subtotal, $discount->value);
        }

        // S'assurer de ne pas dépasser le sous-total
        $orderDiscountAmount = min($orderDiscountAmount, $subtotal);

        if ($orderDiscountAmount <= 0) {
            return DiscountEvaluationResultDTO::failure(
                'Ce code ne génère aucune remise sur le montant de cette commande.'
            );
        }

        return DiscountEvaluationResultDTO::success(
            orderDiscountAmount: $orderDiscountAmount
        );
    }

    /**
     * Calcule la réduction de type 'shipping' (livraison gratuite ou réduction)
     */
    private function calculateShippingDiscount(DiscountApplicationDTO $dto): DiscountEvaluationResultDTO
    {
        $discount = $dto->discount;

        if ($discount->value_type === 'free_shipping') {
            return DiscountEvaluationResultDTO::success(
                freeShipping: true
            );
        }

        // Si tu veux gérer un montant fixe sur le shipping,
        // tu pourras calculer un shippingDiscountAmount ici
        return DiscountEvaluationResultDTO::failure(
            'Ce code ne génère aucune remise sur les frais de livraison pour ce panier.'
        );
    }

    /**
     * Calcule la réduction de type 'product' (réduction par ligne de produit)
     */
    private function calculateProductDiscount(DiscountApplicationDTO $dto, array $eligibleProductIds): DiscountEvaluationResultDTO
    {
        $discount = $dto->discount;
        $productScope = $discount->product_scope ?? 'all';

        $productDiscountAmount = 0.0;
        $lineAdjustments = [];

        foreach ($dto->items as $item) {
            $productId = $item->id;

            // Vérifier éligibilité produit
            if ($productScope !== 'all' && ! in_array($productId, $eligibleProductIds, true)) {
                continue;
            }

            $lineSubtotal = $item->getLineSubtotal();
            $lineDiscount = 0.0;

            if ($discount->value_type === 'percentage' && $discount->value > 0) {
                $lineDiscount = round($lineSubtotal * ($discount->value / 100), 2);
            } elseif ($discount->value_type === 'fixed_amount' && $discount->value > 0) {
                $lineDiscount = min($lineSubtotal, $discount->value);
            }

            if ($lineDiscount <= 0) {
                continue;
            }

            $productDiscountAmount += $lineDiscount;

            $lineAdjustments[] = [
                'id' => $productId,
                'variant_id' => $item->variantId,
                'code' => $discount->code,
                'type' => 'product',
                'qty' => $item->qty,
                'unit_price' => $item->price,
                'discount_amount' => $lineDiscount,
                'final_line_price' => max(0, $lineSubtotal - $lineDiscount),
                'is_gift' => false,
            ];
        }

        // S'assurer de ne pas dépasser le sous-total
        $productDiscountAmount = min($productDiscountAmount, $dto->subtotal);

        if ($productDiscountAmount <= 0) {
            $message = match ($productScope) {
                'all' => 'Ce code ne génère aucune remise sur les produits de votre panier.',
                'products' => "Ce code ne s'applique à aucun des produits présents dans votre panier.",
                'collections' => "Ce code ne s'applique à aucune des collections présentes dans votre panier.",
                default => 'Ce code ne génère aucune remise pour ce panier.',
            };

            return DiscountEvaluationResultDTO::failure($message);
        }

        return DiscountEvaluationResultDTO::success(
            productDiscountAmount: $productDiscountAmount,
            lineAdjustments: $lineAdjustments
        );
    }

    /**
     * Calcule la réduction de type 'buy_x_get_y' (Achetez X, obtenez Y gratuitement)
     */
    private function calculateBuyXGetYDiscount(DiscountApplicationDTO $dto, array $eligibleProductIds): DiscountEvaluationResultDTO
    {
        $discount = $dto->discount;
        $productScope = $discount->product_scope ?? 'all';

        $buyX = (int) ($discount->buy_quantity ?? 0);
        $getY = (int) ($discount->get_quantity ?? 0);
        $groupSize = $buyX + $getY;

        if ($buyX <= 0 || $getY <= 0 || $groupSize <= 0) {
            return DiscountEvaluationResultDTO::failure(
                "Cette réduction n'est pas correctement configurée (paramètres Buy X Get Y manquants)."
            );
        }

        $productDiscountAmount = 0.0;
        $lineAdjustments = [];
        $appliedSomething = false;

        foreach ($dto->items as $item) {
            $productId = $item->id;

            // Vérifier éligibilité produit
            if ($productScope !== 'all' && ! in_array($productId, $eligibleProductIds, true)) {
                continue;
            }

            $qty = $item->qty;
            $price = $item->price;

            // Nombre de groupes complets buy+get
            $groups = intdiv($qty, $groupSize);
            $giftQty = $groups * $getY;

            if ($giftQty <= 0) {
                continue;
            }

            // Y gratuit → remise = giftQty * price
            $lineGiftAmount = $giftQty * $price;

            if ($lineGiftAmount <= 0) {
                continue;
            }

            $appliedSomething = true;
            $productDiscountAmount += $lineGiftAmount;

            $lineAdjustments[] = [
                'id' => $productId,
                'variant_id' => $item->variantId,
                'code' => $discount->code,
                'type' => 'buy_x_get_y',
                'qty' => $giftQty,
                'unit_price' => $price,
                'discount_amount' => $lineGiftAmount,
                'final_line_price' => ($qty * $price) - $lineGiftAmount,
                'is_gift' => true,
            ];
        }

        // Aucun groupe complet trouvé
        if (! $appliedSomething) {
            return DiscountEvaluationResultDTO::failure(
                sprintf(
                    'Ce code nécessite au moins %d article(s) éligible(s) dans le panier (offre %d acheté(s) = %d offert(s)).',
                    $groupSize,
                    $buyX,
                    $getY
                )
            );
        }

        // S'assurer de ne pas dépasser le sous-total
        $productDiscountAmount = min($productDiscountAmount, $dto->subtotal);

        if ($productDiscountAmount <= 0) {
            return DiscountEvaluationResultDTO::failure(
                "Les conditions de l'offre ne sont pas remplies (quantité d'articles éligibles insuffisante)."
            );
        }

        return DiscountEvaluationResultDTO::success(
            productDiscountAmount: $productDiscountAmount,
            lineAdjustments: $lineAdjustments
        );
    }
}
