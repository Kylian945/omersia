"use client";

import { useState } from "react";
import { AddToCartButton } from "./AddToCartButton";

type SimpleAddToCartProps = {
  productId: number;
  name: string;
  price: number;
  oldPrice?: number;
  imageUrl?: string;
  disabled?: boolean;
};

export function SimpleAddToCart({
  productId,
  name,
  price,
  oldPrice,
  imageUrl,
  disabled,
}: SimpleAddToCartProps) {
  const [quantity, setQuantity] = useState(1);

  return (
    <div>
      <label
        htmlFor="quantity"
        className="block text-xs text-neutral-700"
      >
        Quantité
      </label>
      <div className="flex items-center gap-2">
        <input
          id="quantity"
          type="number"
          min={1}
          value={quantity}
          onChange={(e) => {
            const val = Number(e.target.value);
            setQuantity(Number.isNaN(val) || val < 1 ? 1 : val);
          }}
          className="w-16 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs text-neutral-800 focus:outline-none focus:ring-1 focus:ring-black/70"
        />
        <AddToCartButton
          productId={productId}
          name={name}
          price={price}
          oldPrice={oldPrice}
          imageUrl={imageUrl}
          disabled={disabled}
          quantity={quantity}
        />
      </div>
    </div>
  );
}
