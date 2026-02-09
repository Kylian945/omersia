'use client';

import { useEffect } from 'react';
import { Button } from '@/components/common/Button';
import { logger } from '@/lib/logger';

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // Log the error to an error reporting service
    logger.error('Global error caught:', error);
  }, [error]);

  return (
    <html lang="fr">
      <body className="bg-[#f6f6f7] text-[#111827] antialiased">
        <div className="min-h-screen flex items-center justify-center px-4">
          <div className="max-w-2xl w-full text-center">
            <div className="mb-8">
              <h1 className="text-9xl font-black text-gray-200">500</h1>
              <div className="mt-4">
                <h2 className="text-3xl font-bold text-gray-900 mb-2">
                  Erreur serveur
                </h2>
                <p className="text-gray-600 text-lg">
                  Une erreur inattendue s&apos;est produite. Notre équipe a été notifiée.
                </p>
                {error.digest && (
                  <p className="text-sm text-gray-500 font-mono bg-gray-100 px-4 py-2 rounded-lg inline-block mt-4">
                    Code d&apos;erreur : {error.digest}
                  </p>
                )}
              </div>
            </div>

            <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
              <Button
                onClick={reset}
                variant="primary"
                size="lg"
              >
                Réessayer
              </Button>
              <Button
                href="/"
                variant="secondary"
                size="lg"
              >
                Retour à l&apos;accueil
              </Button>
            </div>

            <div className="mt-12 pt-8 border-t border-gray-200">
              <p className="text-sm text-gray-500">
                Nous travaillons activement à résoudre ce problème.
              </p>
            </div>
          </div>
        </div>
      </body>
    </html>
  );
}
