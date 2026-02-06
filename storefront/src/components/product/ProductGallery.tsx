"use client";

import { useState } from "react";
import { OptimizedImage } from "@/components/common/OptimizedImage";

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
  const [selected, setSelected] = useState<NormalizedImage | null>(
    mainImage || images[0] || null
  );

  const hasImages = images && images.length > 0;

  return (
    <section className="space-y-3">
      {/* Image principale */}
      <div className="aspect-square w-full rounded-2xl border border-neutral-200 bg-white flex items-center justify-center overflow-hidden relative">
        {hasImages && selected?.url ? (
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
            const isActive = selected && img.id === selected.id;
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
