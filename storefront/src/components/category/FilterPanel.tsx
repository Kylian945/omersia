/**
 * Panneau filtres réutilisé (sidebar desktop + drawer mobile)
 */
type FilterPanelProps = {
    search: string;
    setSearch: (v: string) => void;
    priceMin: number;
    setPriceMin: (v: number) => void;
    priceMax: number;
    setPriceMax: (v: number) => void;
    bounds: { min: number; max: number };
    inStockOnly: boolean;
    setInStockOnly: (v: boolean) => void;
};

export function FilterPanel({
    search,
    setSearch,
    priceMin,
    setPriceMin,
    priceMax,
    setPriceMax,
    bounds,
    inStockOnly,
    setInStockOnly,
}: FilterPanelProps) {
    const { min, max } = bounds;

    const handleMinChange = (value: number) => {
        if (value > priceMax) {
            setPriceMin(priceMax);
        } else {
            setPriceMin(value);
        }
    };

    const handleMaxChange = (value: number) => {
        if (value < priceMin) {
            setPriceMax(priceMin);
        } else {
            setPriceMax(value);
        }
    };

    // Pour le background du double range (barre remplie entre min & max)
    const rangePercentMin = ((priceMin - min) / (max - min || 1)) * 100;
    const rangePercentMax = ((priceMax - min) / (max - min || 1)) * 100;

    return (
        <div className="space-y-3 text-xxxs">
            {/* Search */}
            <div className="space-y-1">
                <div className="text-xxxs font-medium text-neutral-700">
                    Recherche
                </div>
                <input
                    type="search"
                    placeholder="Nom ou SKU..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-full rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xxxs text-neutral-800 focus:outline-none focus:ring-1 focus:ring-black/70"
                />
            </div>

            {/* Prix - double slider */}
            <div className="space-y-1">
                <div className="text-xxxs font-medium text-neutral-700">
                    Prix ({priceMin}€ – {priceMax}€)
                </div>

                <div className="relative h-7 select-none">
                    {/* Track gris */}
                    <div className="absolute inset-x-0 top-1/2 -translate-y-1/2 h-[3px] rounded-full bg-neutral-200" />

                    {/* Track actif */}
                    <div
                        className="absolute top-1/2 -translate-y-1/2 h-[3px] rounded-full bg-neutral-900 pointer-events-none"
                        style={{
                            left: `${rangePercentMin}%`,
                            width: `${rangePercentMax - rangePercentMin}%`,
                        }}
                    />

                    {/* Slider MIN */}
                    <input
                        type="range"
                        min={min}
                        max={max}
                        value={priceMin}
                        onChange={(e) => handleMinChange(Number(e.target.value))}
                        className="
        absolute inset-0 z-20 w-full appearance-none bg-transparent
        pointer-events-none
        [&::-webkit-slider-thumb]:appearance-none
        [&::-webkit-slider-thumb]:h-3 [&::-webkit-slider-thumb]:w-3
        [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-black
        [&::-webkit-slider-thumb]:pointer-events-auto
        [&::-moz-range-thumb]:h-3 [&::-moz-range-thumb]:w-3
        [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-black
        [&::-moz-range-thumb]:pointer-events-auto
        [&::-webkit-slider-runnable-track]:bg-transparent
        [&::-moz-range-track]:bg-transparent
      "
                    />

                    {/* Slider MAX */}
                    <input
                        type="range"
                        min={min}
                        max={max}
                        value={priceMax}
                        onChange={(e) => handleMaxChange(Number(e.target.value))}
                        className="
        absolute inset-0 z-30 w-full appearance-none bg-transparent
        pointer-events-none
        [&::-webkit-slider-thumb]:appearance-none
        [&::-webkit-slider-thumb]:h-3 [&::-webkit-slider-thumb]:w-3
        [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-black
        [&::-webkit-slider-thumb]:pointer-events-auto
        [&::-moz-range-thumb]:h-3 [&::-moz-range-thumb]:w-3
        [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-black
        [&::-moz-range-thumb]:pointer-events-auto
        [&::-webkit-slider-runnable-track]:bg-transparent
        [&::-moz-range-track]:bg-transparent
      "
                    />
                </div>
            </div>

            {/* Stock */}
            <div className="space-y-1">
                <div className="text-xxxs font-medium text-neutral-700">
                    Disponibilité
                </div>
                <label className="inline-flex items-center gap-1.5 text-xxxs text-neutral-700">
                    <input
                        type="checkbox"
                        checked={inStockOnly}
                        onChange={(e) => setInStockOnly(e.target.checked)}
                        className="h-3 w-3 rounded border-neutral-300"
                    />
                    <span>En stock uniquement</span>
                </label>
            </div>

            {/* Reset rapide */}
            <button
                type="button"
                onClick={() => {
                    setSearch("");
                    setPriceMin(min);
                    setPriceMax(max);
                    setInStockOnly(false);
                }}
                className="mt-1 text-xxxs text-neutral-500 hover:text-black hover:underline underline-offset-2"
            >
                Réinitialiser les filtres
            </button>
        </div>
    );
}