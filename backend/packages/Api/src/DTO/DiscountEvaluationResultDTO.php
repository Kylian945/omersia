<?php

namespace Omersia\Api\DTO;

/**
 * DTO pour le résultat de l'évaluation d'une réduction
 */
class DiscountEvaluationResultDTO
{
    /**
     * @param  bool  $ok  Si la réduction est applicable
     * @param  string|null  $message  Message d'erreur si non applicable
     * @param  float  $orderDiscountAmount  Montant de remise sur la commande
     * @param  float  $productDiscountAmount  Montant de remise sur les produits
     * @param  float  $shippingDiscountAmount  Montant de remise sur la livraison
     * @param  float  $totalDiscount  Remise totale
     * @param  bool  $freeShipping  Si la livraison est gratuite
     * @param  array  $lineAdjustments  Ajustements par ligne de panier
     */
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $message = null,
        public readonly float $orderDiscountAmount = 0.0,
        public readonly float $productDiscountAmount = 0.0,
        public readonly float $shippingDiscountAmount = 0.0,
        public readonly float $totalDiscount = 0.0,
        public readonly bool $freeShipping = false,
        public readonly array $lineAdjustments = [],
    ) {}

    /**
     * Crée un résultat d'échec avec un message
     */
    public static function failure(string $message): self
    {
        return new self(
            ok: false,
            message: $message,
        );
    }

    /**
     * Crée un résultat de succès
     */
    public static function success(
        float $orderDiscountAmount = 0.0,
        float $productDiscountAmount = 0.0,
        float $shippingDiscountAmount = 0.0,
        bool $freeShipping = false,
        array $lineAdjustments = [],
    ): self {
        $totalDiscount = $orderDiscountAmount + $productDiscountAmount + $shippingDiscountAmount;

        return new self(
            ok: true,
            orderDiscountAmount: $orderDiscountAmount,
            productDiscountAmount: $productDiscountAmount,
            shippingDiscountAmount: $shippingDiscountAmount,
            totalDiscount: $totalDiscount,
            freeShipping: $freeShipping,
            lineAdjustments: $lineAdjustments,
        );
    }

    /**
     * Convertir en tableau pour la réponse API
     */
    public function toArray(): array
    {
        if (! $this->ok) {
            return [
                'ok' => false,
                'message' => $this->message,
            ];
        }

        return [
            'ok' => true,
            'order_discount_amount' => $this->orderDiscountAmount,
            'product_discount_amount' => $this->productDiscountAmount,
            'shipping_discount_amount' => $this->shippingDiscountAmount,
            'total_discount' => $this->totalDiscount,
            'free_shipping' => $this->freeShipping,
            'line_adjustments' => $this->lineAdjustments,
        ];
    }
}
