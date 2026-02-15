<div id="content-ai-prompt-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 backdrop-blur-sm px-4">
    <div class="w-full max-w-lg rounded-2xl border border-black/5 bg-white p-4 shadow-xl">
        <div class="flex items-start justify-between gap-2">
            <div>
                <div class="text-xs font-semibold text-gray-900">Assistant IA</div>
                <div class="text-xxxs text-gray-500">
                    Décrivez le résultat attendu (angle SEO, ton, contraintes métier).
                </div>
            </div>
            <button type="button" id="content-ai-modal-close-button"
                class="text-xs text-gray-400 hover:text-gray-700" aria-label="Fermer la modal IA">
                ✕
            </button>
        </div>

        <div class="mt-3 space-y-2">
            @if (!($hasConfiguredAiProvider ?? false))
                <p class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-xxxs text-amber-800">
                    Aucun provider IA actif. Configurez-en un dans
                    <a href="{{ $aiProviderSettingsUrl ?? route('admin.settings.ai.index') }}"
                        class="font-semibold underline decoration-amber-500/70">
                        Paramètres > IA
                    </a>.
                </p>
            @endif
            <p class="text-xxxs text-gray-500">
                Champ ciblé: <span id="content-ai-target-label" class="font-semibold text-gray-700">-</span>
            </p>
            <label for="content-ai-prompt-input" class="block text-xs font-medium text-gray-700">
                Prompt de génération
            </label>
            <textarea id="content-ai-prompt-input" rows="5" maxlength="2000"
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs resize-y"
                placeholder="Ex: Rédige une meta description orientée conversion, claire, sans promesse exagérée."></textarea>
            <p class="text-xxxs text-gray-500">
                Le prompt est combiné avec la configuration IA globale (contexte marque, ton, limites SEO).
            </p>
            <p id="content-ai-modal-error" class="hidden text-xxs text-red-600"></p>
        </div>

        <div class="mt-3 flex items-center justify-end gap-2">
            <button type="button" id="content-ai-modal-cancel-button"
                class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50">
                Annuler
            </button>
            <button type="button" id="content-ai-modal-submit-button"
                class="rounded-lg bg-neutral-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-black disabled:opacity-60 disabled:cursor-not-allowed">
                Lancer la génération
            </button>
        </div>
    </div>
</div>
