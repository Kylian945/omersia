"use client";

import Link from "next/link";
import { OptimizedImage } from "@/components/common/OptimizedImage";
import { useCallback, useEffect, useState } from "react";
import useEmblaCarousel from "embla-carousel-react";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { Category } from "@/lib/types/category-types";

type CategoriesSliderProps = {
  categories: Category[];
  slidesPerView?: { desktop: number; mobile: number };
  slidesToScroll?: { desktop: number; mobile: number };
  showArrows?: boolean;
  showDots?: boolean;
  autoplay?: boolean;
  gap?: number;
};

export function CategoriesSlider({
  categories,
  slidesPerView = { desktop: 4, mobile: 2 },
  slidesToScroll = { desktop: 1, mobile: 1 },
  showArrows = true,
  showDots = true,
  autoplay = false,
  gap = 16,
}: CategoriesSliderProps) {
  // Determine slidesToScroll based on screen size
  const [currentSlidesToScroll, setCurrentSlidesToScroll] = useState(slidesToScroll.mobile);

  useEffect(() => {
    const handleResize = () => {
      const isDesktop = window.innerWidth >= 768;
      setCurrentSlidesToScroll(isDesktop ? slidesToScroll.desktop : slidesToScroll.mobile);
    };
    handleResize();
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, [slidesToScroll]);

  const [emblaRef, emblaApi] = useEmblaCarousel({
    loop: true,
    align: "start",
    slidesToScroll: currentSlidesToScroll,
  });

  const [selectedIndex, setSelectedIndex] = useState(0);
  const [scrollSnaps, setScrollSnaps] = useState<number[]>([]);

  // Navigation handlers
  const scrollPrev = useCallback(() => {
    if (emblaApi) emblaApi.scrollPrev();
  }, [emblaApi]);

  const scrollNext = useCallback(() => {
    if (emblaApi) emblaApi.scrollNext();
  }, [emblaApi]);

  const scrollTo = useCallback(
    (index: number) => {
      if (emblaApi) emblaApi.scrollTo(index);
    },
    [emblaApi]
  );

  // Initialize scroll snaps and selection
  useEffect(() => {
    if (!emblaApi) return;

    const onSelect = () => {
      setSelectedIndex(emblaApi.selectedScrollSnap());
    };

    const onInit = () => {
      setScrollSnaps(emblaApi.scrollSnapList());
    };

    emblaApi.on("select", onSelect);
    emblaApi.on("init", onInit);
    emblaApi.on("reInit", onInit);

    onInit();
    onSelect();

    return () => {
      emblaApi.off("select", onSelect);
      emblaApi.off("init", onInit);
      emblaApi.off("reInit", onInit);
    };
  }, [emblaApi]);

  // Reinitialize carousel when slidesToScroll changes
  useEffect(() => {
    if (!emblaApi) return;
    emblaApi.reInit({ slidesToScroll: currentSlidesToScroll });
  }, [emblaApi, currentSlidesToScroll]);

  // Autoplay functionality
  useEffect(() => {
    if (!autoplay || !emblaApi) return;

    const interval = setInterval(() => {
      emblaApi.scrollNext();
    }, 3000);

    return () => clearInterval(interval);
  }, [autoplay, emblaApi]);

  if (!categories || categories.length === 0) return null;

  return (
    <div className="embla">
      <div className="embla__viewport" ref={emblaRef}>
        <div className="embla__container">
          {categories.map((category) => (
            <div
              key={category.id}
              className="embla__slide pb-4"
              data-slides-mobile={slidesPerView.mobile}
              data-slides-desktop={slidesPerView.desktop}
              style={{ "--slide-gap": `${gap}px` } as React.CSSProperties}
            >
              <Link
                href={`/categories/${category.slug}`}
                className="group relative overflow-hidden rounded-xl transition-transform hover:scale-105 border border-[var(--theme-border-default,#e5e7eb)] block"
                style={{
                  backgroundColor: "var(--theme-card-bg, #ffffff)",
                  borderRadius: "var(--theme-border-radius, 12px)",
                }}
              >
                {/* Image */}
                <div className="relative aspect-square overflow-hidden">
                  {category.image ? (
                    <OptimizedImage
                      src={category.image}
                      alt={category.name}
                      fill
                      sizes="(max-width: 768px) 50vw, 33vw"
                      className="object-cover transition-transform duration-300 group-hover:scale-110"
                      fallback={<div className="h-full w-full bg-[var(--theme-input-bg,#ffffff)]" />}
                    />
                  ) : (
                    <div className="h-full w-full bg-[var(--theme-input-bg,#ffffff)]" />
                  )}

                  {/* Overlay on hover */}
                  <div className="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/20" />
                </div>

                {/* Category Info */}
                <div className="p-4">
                  <div
                    className="text-sm font-semibold"
                    style={{ color: "var(--theme-heading-color, #111827)" }}
                  >
                    {category.name}
                  </div>
                  {category.count !== undefined && (
                    <p
                      className="mt-1 text-xs"
                      style={{ color: "var(--theme-muted-color, #6b7280)" }}
                    >
                      {category.count} produit{category.count > 1 ? "s" : ""}
                    </p>
                  )}
                </div>
              </Link>
            </div>
          ))}
        </div>
      </div>

      {(showArrows || showDots) && (
        <div className="mt-2 flex items-center justify-between gap-2">
          {/* Dots */}
          {showDots && scrollSnaps.length > 0 && (
            <div className="flex gap-1.5">
              {scrollSnaps.map((_, index) => (
                <button
                  key={index}
                  type="button"
                  onClick={() => scrollTo(index)}
                  className={`h-1.5 rounded-full transition-all ${
                    index === selectedIndex
                      ? "w-6 bg-[var(--theme-primary,#111827)]"
                      : "w-1.5 bg-[var(--theme-border-default,#e5e7eb)] hover:bg-[var(--theme-muted-color,#6b7280)]"
                  }`}
                  aria-label={`Aller à la slide ${index + 1}`}
                />
              ))}
            </div>
          )}

          {/* Arrows */}
          {showArrows && (
            <div className="flex items-center gap-1.5 text-xxxs text-[var(--theme-muted-color,#6b7280)] ml-auto">
              <span className="hidden sm:inline">Faire défiler</span>
              <div className="flex gap-1">
                <button
                  type="button"
                  onClick={scrollPrev}
                  className="inline-flex items-center justify-center h-6 w-6 rounded-full border border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-card-bg,#ffffff)] hover:border-[var(--theme-border-hover,#111827)] hover:text-[var(--theme-heading-color,#111827)] transition"
                  aria-label="Précédent"
                >
                  <ChevronLeft className="w-3 h-3" />
                </button>
                <button
                  type="button"
                  onClick={scrollNext}
                  className="inline-flex items-center justify-center h-6 w-6 rounded-full border border-[var(--theme-border-default,#e5e7eb)] bg-[var(--theme-card-bg,#ffffff)] hover:border-[var(--theme-border-hover,#111827)] hover:text-[var(--theme-heading-color,#111827)] transition"
                  aria-label="Suivant"
                >
                  <ChevronRight className="w-3 h-3" />
                </button>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
