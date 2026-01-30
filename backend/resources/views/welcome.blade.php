<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Omersia Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-soft">

    <div class="min-h-screen flex items-center">
        <div class="w-full max-w-6xl mx-auto px-6 py-6 flex items-center justify-center">
            <div class="flex flex-col justify-center items-center max-w-lg">
                {{-- Colonne gauche : Hero --}}
                <div class="inline-flex items-center gap-2 mb-4 self-start">
                        <div
                            class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center shadow-sm font-bold">
                            O
                        </div>
                        <div class="flex flex-col leading-tight">
                            <span class="pill-label">Omersia • Admin</span>
                            <span class="text-xs text-gray-500">Back-office e-commerce unifié</span>
                        </div>
                    </div>
                <div class="p-8 border border-gray-200 rounded-lg text-center">
                    

                    <h1 class="text-2xl md:text-3xl font-semibold text-gray-900">
                        Bienvenue sur votre console
                        <span class="text-emerald-700">Omersia</span>
                    </h1>

                    <p class="mt-3 text-sm text-gray-600 max-w-md mx-auto">
                        Gérez votre catalogue produits, vos contenus et vos réglages à partir d’un back-office
                        clair, rapide et inspiré de l’expérience Shopify. Connectez-vous pour accéder à votre espace
                        sécurisé.
                    </p>

                    <div class="mt-4 flex flex-wrap justify-center gap-3">
                        @auth
                            <a href="{{ url('/admin') }}" class="text-xs bg-black text-white hover:bg-neutral-900 px-4 py-2 rounded-full shadow-sm">
                                Entrer dans Omersia Admin
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-xs bg-black text-white hover:bg-neutral-900 px-4 py-2 rounded-full shadow-sm">
                                Se connecter à Omersia Admin
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-xs bg-white text-black hover:bg-gray-100 px-4 py-2 rounded-full shadow-sm border border-gray-300">
                                    Inviter un membre de l’équipe
                                </a>
                            @endif
                        @endauth
                    </div>

                    <div class="mt-4 flex items-center justify-center gap-3 text-xs text-gray-500">
                        <div class="badge">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>API & Storefront headless connectés</span>
                        </div>
                        <span>Single source of truth pour vos produits & contenus.</span>
                    </div>


                </div>
                <div class="mt-4 text-xs text-gray-400">
                    Laravel v{{ Illuminate\Foundation\Application::VERSION }}
                    • PHP v{{ PHP_VERSION }}
                    • Propulsé par Omersia Admin
                </div>
            </div>

        </div>
    </div>

</body>

</html>
