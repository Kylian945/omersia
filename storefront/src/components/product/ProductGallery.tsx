"use client";

import { useEffect, useMemo, useState } from "react";
import { OptimizedImage } from "@/components/common/OptimizedImage";
import { useOptionalProductVariant } from "./ProductVariantContext";

type NormalizedImage = {
  id: number;
  url: string;
  is_main?: boolean;
  position?: number | null;
};

type Props = {
  images: NormalizedImage[];
  mainImage: NormalizedImage | null;
  alt: string;
};

export function ProductGallery({ images, mainImage, alt }: Props) {
  const variantContext = useOptionalProductVariant();
  const matchingVariantId = variantContext?.matchingVariant?.id || null;
  const matchingVariantImageId = variantContext?.matchingVariantImageId || null;
  const variantImageUrl = variantContext?.variantImageUrl || null;

  const fallbackMainImage = mainImage || images[0] || null;
  const imagesById = useMemo(
    () => new Map(images.map((image) => [image.id, image])),
    [images]
  );

  const [selected, setSelected] = useState<NormalizedImage | null>(
    fallbackMainImage
  );

  useEffect(() => {
    if (!variantContext) {
      setSelected((current) => current || fallbackMainImage);
      return;
    }

    if (
      matchingVariantImageId !== null &&
      imagesById.has(matchingVariantImageId)
    ) {
      setSelected(imagesById.get(matchingVariantImageId) || null);
      return;
    }

    if (variantImageUrl) {
      const imageFromList = images.find((image) => image.url === variantImageUrl);
      if (imageFromList) {
        setSelected(imageFromList);
        return;
      }

      // Defensive fallback: display the variant image even if not present in gallery list.
      setSelected({
        id: -1,
        url: variantImageUrl,
      });
      return;
    }

    if (matchingVariantId !== null) {
      setSelected(fallbackMainImage);
      return;
    }

    setSelected(fallbackMainImage);
  }, [
    variantContext,
    matchingVariantId,
    matchingVariantImageId,
    variantImageUrl,
    imagesById,
    images,
    fallbackMainImage,
  ]);

  const hasImages = images.length > 0;
  const canDisplayImage = typeof selected?.url === "string" && selected.url !== "";

  return (
    <section className="space-y-3">
      {/* Image principale */}
      <div className="aspect-square w-full rounded-2xl border border-neutral-200 bg-white flex items-center justify-center overflow-hidden relative">
        {canDisplayImage ? (
          <OptimizedImage
            src={selected.url}
            alt={alt}
            fill
            sizes="(max-width: 768px) 100vw, 50vw"
            className="object-cover"
            priority
            fallback={
              <div className="flex flex-col items-center justify-center text-xs text-neutral-400 h-full w-full">
                <div className="w-10 h-10 rounded-full border border-dashed border-neutral-300 mb-2" />
                Image non disponible
              </div>
            }
          />
        ) : (
          <div className="flex flex-col items-center justify-center text-xs text-neutral-400">
            <div className="w-10 h-10 rounded-full border border-dashed border-neutral-300 mb-2" />
            Aucune image disponible pour le moment.
          </div>
        )}
      </div>

      {/* Miniatures */}
      {hasImages && images.length > 1 && (
        <div className="grid grid-flow-col auto-cols-[72px] gap-2 overflow-x-auto p-1">
          {images.map((img) => {
            const isActive = selected
              ? selected.id > 0
                ? img.id === selected.id
                : img.url === selected.url
              : false;
            return (
              <button
                key={img.id}
                type="button"
                onClick={() => setSelected(img)}
                className={`relative h-18 w-18 min-w-[72px] aspect-square rounded-xl border overflow-hidden transition
                  ${
                    isActive
                      ? "border-black ring-1 ring-black"
                      : "border-neutral-200 hover:border-black/50"
                  }
                `}
              >
                <OptimizedImage
                  src={img.url}
                  alt={alt}
                  fill
                  sizes="72px"
                  className="object-cover"
                />
              </button>
            );
          })}
        </div>
      )}
    </section>
  );
}
