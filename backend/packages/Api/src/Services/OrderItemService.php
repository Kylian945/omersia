<?php

declare(strict_types=1);

namespace Omersia\Api\Services;

use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\OrderItem;

/**
 * Service pour gérer les items d'une commande
 */
class OrderItemService
{
    /**
     * Synchroniser les items d'une commande
     * Supprime les anciens items et crée les nouveaux
     */
    public function syncItems(Order $order, array $items): void
    {
        // Supprimer les anciens items
        $order->items()->delete();

        // Créer les nouveaux items
        foreach ($items as $item) {
            $this->createItem($order, $item);
        }
    }

    /**
     * Créer un item de commande
     */
    public function createItem(Order $order, array $itemData): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $itemData['product_id'] ?? null,
            'variant_id' => $itemData['variant_id'] ?? null,
            'name' => $itemData['name'],
            'sku' => $itemData['sku'] ?? null,
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'],
            'total_price' => $itemData['total_price'],
            'meta' => $itemData['meta'] ?? [],
        ]);
    }
}
