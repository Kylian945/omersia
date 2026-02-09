'use client';

import { useEffect } from 'react';
import { Button } from '@/components/common/Button';
import { logger } from '@/lib/logger';

/**
 * DCA-010: Error boundary pour les pages catégorie
 */
export default function CategoryError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    logger.error('Category page error:', error);
  }, [error]);

  return (
    <div className="min-h-[60vh] bg-neutral-50 flex items-center justify-center px-4">
      <div className="max-w-md w-full text-center">
        <div className="mb-6">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 mb-4">
            <svg
              className="w-8 h-8 text-amber-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
              />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">
            Catégorie indisponible
          </h2>
          <p className="text-gray-600 mb-4">
            Cette catégorie n&apos;a pas pu être chargée. Veuillez réessayer.
          </p>
          {error.digest && (
            <p className="text-xs text-gray-400 font-mono">
              Ref: {error.digest}
            </p>
          )}
        </div>

        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <Button onClick={reset} variant="primary" size="md">
            Réessayer
          </Button>
          <Button href="/products" variant="secondary" size="md">
            Parcourir les produits
          </Button>
        </div>
      </div>
    </div>
  );
}
