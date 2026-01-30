"use client";

import { ReactNode, useCallback, useEffect, useState } from "react";
import useEmblaCarousel from "embla-carousel-react";
import { ChevronLeft, ChevronRight } from "lucide-react";

type ShellProps = {
  children: ReactNode;
  slidesPerView?: { desktop: number; mobile: number };
  slidesToScroll?: { desktop: number; mobile: number };
  showArrows?: boolean;
  showDots?: boolean;
  autoplay?: boolean;
};

export function ProductSliderShell({
  children,
  slidesPerView = { desktop: 4, mobile: 2 },
  slidesToScroll = { desktop: 1, mobile: 1 },
  showArrows = true,
  showDots = true,
  autoplay = false,
}: ShellProps) {
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

  return (
    <div className="embla">
      <div className="embla__viewport" ref={emblaRef}>
        <div className="embla__container">
          {children}
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
                      ? "w-6 bg-neutral-900"
                      : "w-1.5 bg-neutral-300 hover:bg-neutral-400"
                  }`}
                  aria-label={`Aller à la slide ${index + 1}`}
                />
              ))}
            </div>
          )}

          {/* Arrows */}
          {showArrows && (
            <div className="flex items-center gap-1.5 text-xxxs text-neutral-500 ml-auto">
              <span className="hidden sm:inline">Faire défiler</span>
              <div className="flex gap-1">
                <button
                  type="button"
                  onClick={scrollPrev}
                  className="inline-flex items-center justify-center h-6 w-6 rounded-full border border-neutral-200 bg-white hover:border-black/60 hover:text-black transition"
                  aria-label="Précédent"
                >
                  <ChevronLeft className="w-3 h-3" />
                </button>
                <button
                  type="button"
                  onClick={scrollNext}
                  className="inline-flex items-center justify-center h-6 w-6 rounded-full border border-neutral-200 bg-white hover:border-black/60 hover:text-black transition"
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
