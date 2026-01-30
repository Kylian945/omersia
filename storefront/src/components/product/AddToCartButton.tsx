"use client";

import { useCart } from "@/components/cart/CartContext";
import { Button } from "@/components/common/Button";

type Props = {
  productId: number;
  name: string;
  price: number;
  oldPrice?: number;
  imageUrl?: string | null;
  disabled?: boolean;
  quantity?: number;
  variantId?: number;
  variantLabel?: string;
};

export function AddToCartButton({
  productId,
  name,
  price,
  oldPrice,
  imageUrl,
  disabled,
  quantity = 1,
  variantId,
  variantLabel,
}: Props) {
  const { addItem, openCart } = useCart();

  const handleClick = () => {
    if (disabled || !price || price <= 0) return;
    addItem({
      id: productId,
      name,
      price,
      oldPrice,
      imageUrl,
      qty: quantity > 0 ? quantity : 1,
      variantId,
      variantLabel,
    });
    openCart();
  };

  return (
    <Button
      type="button"
      disabled={disabled || !price || price <= 0}
      onClick={handleClick}
      variant="primary"
      size="md"
      className="flex-1"
    >
      Ajouter au panier
    </Button>
  );
}
