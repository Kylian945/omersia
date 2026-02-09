"use client";

import { useRouter, useSearchParams } from "next/navigation";

type PaginationProps = {
  currentPage: number;
  lastPage: number;
  total: number;
  perPage: number;
  from: number | null;
  to: number | null;
  onPageChange?: (page: number) => void;
};

export function Pagination({
  currentPage,
  lastPage,
  total,
  perPage: _perPage,
  from,
  to,
  onPageChange,
}: PaginationProps) {
  const router = useRouter();
  const searchParams = useSearchParams();

  if (lastPage <= 1) return null;

  const handlePageChange = (page: number) => {
    if (onPageChange) {
      onPageChange(page);
    } else {
      const params = new URLSearchParams(searchParams);
      params.set("page", page.toString());
      router.push(`?${params.toString()}`);
    }
  };

  const getPageNumbers = () => {
    const pages: (number | string)[] = [];
    const maxVisible = 7;

    if (lastPage <= maxVisible) {
      for (let i = 1; i <= lastPage; i++) {
        pages.push(i);
      }
    } else {
      if (currentPage <= 3) {
        for (let i = 1; i <= 4; i++) {
          pages.push(i);
        }
        pages.push("...");
        pages.push(lastPage);
      } else if (currentPage >= lastPage - 2) {
        pages.push(1);
        pages.push("...");
        for (let i = lastPage - 3; i <= lastPage; i++) {
          pages.push(i);
        }
      } else {
        pages.push(1);
        pages.push("...");
        for (let i = currentPage - 1; i <= currentPage + 1; i++) {
          pages.push(i);
        }
        pages.push("...");
        pages.push(lastPage);
      }
    }

    return pages;
  };

  const pages = getPageNumbers();

  return (
    <div className="flex flex-col items-center gap-4 mt-8">
      {/* Info */}
      <div className="text-sm text-neutral-600">
        Affichage de {from} à {to} sur {total} produits
      </div>

      {/* Pagination */}
      <div className="flex items-center gap-2">
        {/* Bouton Précédent */}
        <button
          onClick={() => handlePageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className={`px-3 py-1.5 text-sm rounded-lg border transition ${
            currentPage === 1
              ? "border-neutral-200 text-neutral-400 cursor-not-allowed"
              : "border-neutral-300 text-neutral-700 hover:bg-neutral-50"
          }`}
        >
          Précédent
        </button>

        {/* Numéros de page */}
        <div className="flex items-center gap-1">
          {pages.map((page, index) =>
            typeof page === "number" ? (
              <button
                key={index}
                onClick={() => handlePageChange(page)}
                className={`min-w-[2.5rem] px-3 py-1.5 text-sm rounded-lg border transition ${
                  currentPage === page
                    ? "bg-black text-white border-black"
                    : "border-neutral-300 text-neutral-700 hover:bg-neutral-50"
                }`}
              >
                {page}
              </button>
            ) : (
              <span
                key={index}
                className="px-2 text-neutral-400 text-sm"
              >
                {page}
              </span>
            )
          )}
        </div>

        {/* Bouton Suivant */}
        <button
          onClick={() => handlePageChange(currentPage + 1)}
          disabled={currentPage === lastPage}
          className={`px-3 py-1.5 text-sm rounded-lg border transition ${
            currentPage === lastPage
              ? "border-neutral-200 text-neutral-400 cursor-not-allowed"
              : "border-neutral-300 text-neutral-700 hover:bg-neutral-50"
          }`}
        >
          Suivant
        </button>
      </div>
    </div>
  );
}
