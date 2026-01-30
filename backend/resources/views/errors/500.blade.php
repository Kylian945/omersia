<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>500 - Erreur serveur • Omersia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-feature-settings: "cv02", "cv03", "cv04", "cv11";
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f6f6f7] text-[#111827] antialiased">

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-2xl w-full">
            {{-- Card principale --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                {{-- Header avec le logo --}}
                <div class="bg-gradient-to-br from-slate-50 to-white border-b border-slate-200/80 px-8 py-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div
                            class="h-10 w-10 rounded-xl bg-black flex items-center justify-center font-black text-white shadow-sm">
                            S
                        </div>
                        <div>
                            <div class="text-sm font-semibold uppercase tracking-[.18em] text-slate-800">Omersia</div>
                            <div class="text-xs text-slate-500">Admin console</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        {{-- Icône d'erreur --}}
                        <div class="flex-shrink-0">
                            <div
                                class="h-16 w-16 rounded-2xl bg-orange-50 border border-orange-100 flex items-center justify-center">
                                <svg class="h-8 w-8 text-orange-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>

                        {{-- Titre et message --}}
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-slate-900 mb-1">Erreur serveur</h1>
                            <p class="text-sm text-slate-600">Code d'erreur : 500 - Internal Server Error</p>
                        </div>
                    </div>
                </div>

                {{-- Contenu --}}
                <div class="px-8 py-6 space-y-6">
                    {{-- Message principal --}}
                    <div class="space-y-3">
                        <p class="text-base text-slate-700 leading-relaxed">
                            Une erreur inattendue s'est produite sur le serveur. Nos équipes techniques ont été
                            notifiées et travaillent à résoudre le problème.
                        </p>
                    </div>

                    {{-- Informations --}}
                    <div class="bg-slate-50 rounded-lg p-5 border border-slate-200/80">
                        <h2 class="text-sm font-semibold text-slate-900 mb-3">Que faire ?</h2>
                        <ul class="space-y-2 text-sm text-slate-700">
                            <li class="flex items-start gap-2">
                                <svg class="h-4 w-4 text-slate-400 mt-0.5 flex-shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="2" />
                                </svg>
                                <span>Réessayez dans quelques instants</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="h-4 w-4 text-slate-400 mt-0.5 flex-shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="2" />
                                </svg>
                                <span>Rafraîchissez la page</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="h-4 w-4 text-slate-400 mt-0.5 flex-shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="2" />
                                </svg>
                                <span>Si le problème persiste, contactez le support technique</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button onclick="window.location.reload()"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-colors duration-150 shadow-sm">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Rafraîchir la page
                        </button>

                        <a href="{{ route('admin.dashboard') }}"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-300 transition-colors duration-150">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Retour au tableau de bord
                        </a>
                    </div>

                    {{-- Info supplémentaire --}}
                    <div class="pt-4 border-t border-slate-200">
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Si cette erreur se reproduit fréquemment, veuillez contacter votre administrateur système
                            avec le code d'erreur et l'heure de l'incident.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="mt-6 text-center">
                <p class="text-xs text-slate-500">
                    © {{ date('Y') }} Omersia. Tous droits réservés.
                </p>
            </div>
        </div>
    </div>

</body>

</html>
