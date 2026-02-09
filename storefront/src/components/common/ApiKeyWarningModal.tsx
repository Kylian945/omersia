"use client";

export function ApiKeyWarningModal({ hasApiKey }: { hasApiKey: boolean }) {
  if (hasApiKey) return null;

  return (
    <div className="fixed inset-0 z-9999 flex items-center justify-center bg-[rgba(0,0,0,0.7)] backdrop-blur-lg">
      <div
        className="bg-white rounded-lg shadow-[0_0_0_1px_rgba(0,0,0,0.05),0_4px_16px_rgba(0,0,0,0.1)] max-w-[560px] w-full mx-4"
        style={{ fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif' }}
      >
        <div className="p-5 border-b border-[#E1E3E5]">
          <div className="flex items-center gap-3">
            <div className="shrink-0 mt-0.5">
              <div className="w-8 h-8 rounded-full bg-[#FED3D1] flex items-center justify-center">
                <svg
                  className="w-5 h-5 text-[#D72C0D]"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fillRule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clipRule="evenodd"
                  />
                </svg>
              </div>
            </div>
            <div className="flex-1">
              <h2 className="text-[#202223] text-[17px] font-semibold leading-6">
                Configuration requise
              </h2>
            </div>
          </div>
        </div>

        <div className="p-5">
          <div className="mb-4">
            <p className="text-[#202223] text-[14px] leading-5">
              Le frontend nécessite une clé API pour fonctionner correctement.
            </p>
          </div>

          <div className="bg-[#FFF4E5] border border-[#FFD79D] rounded-[8px] p-4 mb-4">
            <div className="flex gap-3">
              <div className="shrink-0 mt-0.5">
                <svg className="w-5 h-5 text-[#8A6116]" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="flex-1">
                <p className="text-xs text-[#8A6116] font-medium leading-5">
                  Veuillez configurer la variable d&apos;environnement{" "}
                  <code className="bg-white/60 px-1.5 py-0.5 rounded text-[12px] font-mono border border-[#FFD79D]">
                    FRONT_API_KEY
                  </code>{" "}
                  dans votre fichier de variables d&apos;environnement
                </p>
              </div>
            </div>
          </div>

          <div className="bg-[#F6F6F7] rounded-lg p-4">
            <p className="text-[#202223] text-xs font-semibold mb-3">
              Instructions
            </p>
            <ol className="space-y-2">
              <li className="flex gap-2 text-xs text-[#6D7175] leading-5">
                <span className="text-[#202223] font-semibold">1.</span>
                <span>Créez une clé API dans l&apos;administration</span>
              </li>
              <li className="flex gap-2 text-xs text-[#6D7175] leading-5">
                <span className="text-[#202223] font-semibold">2.</span>
                <span>Ajoutez-la dans le fichier de variables d&apos;environnement</span>
              </li>
              <li className="flex gap-2 text-xs text-[#6D7175] leading-5">
                <span className="text-[#202223] font-semibold">3.</span>
                <span>Redémarrez le serveur de développement</span>
              </li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  );
}
