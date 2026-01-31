'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { Button } from '@/components/common/Button';
import { logger } from '@/lib/logger';

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // Log the error to an error reporting service
    logger.error('Error caught by error boundary:', error);
  }, [error]);

  return (
    <div className="min-h-screen bg-[#f6f6f7] flex items-center justify-center px-4">
      <div className="max-w-2xl w-full text-center">
        <div className="mb-8">
          <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 mb-6">
            <svg
              className="w-10 h-10 text-red-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
          </div>
          <h2 className="text-3xl font-bold text-gray-900 mb-2">
            Une erreur est survenue
          </h2>
          <p className="text-gray-600 text-lg mb-4">
            Nous sommes désolés, quelque chose s&apos;est mal passé.
          </p>
          {error.digest && (
            <p className="text-sm text-gray-500 font-mono bg-gray-100 px-4 py-2 rounded-lg inline-block">
              Code d&apos;erreur : {error.digest}
            </p>
          )}
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
            Si le problème persiste, veuillez{" "}
            <Link href="/contact" className="text-black underline hover:no-underline">
              contacter notre support
            </Link>
            .
          </p>
        </div>
      </div>
    </div>
  );
}
