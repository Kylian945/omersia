<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Omersia Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-soft">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            {{-- Branding --}}
            <div class="flex items-center gap-2 mb-4">
                <div class="w-9 h-9 rounded-full bg-black text-white flex items-center justify-center text-sm font-bold shadow-sm">
                    S
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="text-xxxs uppercase tracking-[.16em] text-gray-400">Omersia • Admin</span>
                    <span class="text-xs text-gray-500">Back-office e-commerce unifié</span>
                </div>
            </div>

            {{-- Card --}}
            <div class="bg-white/90 backdrop-blur-sm border border-gray-200 rounded-lg shadow-sm px-6 py-6">
                {{ $slot }}
            </div>

            {{-- Footer meta --}}
            <div class="mt-3 text-xxxs text-gray-400 text-center">
                Laravel v{{ Illuminate\Foundation\Application::VERSION }}
                • PHP v{{ PHP_VERSION }}
                • Propulsé par Omersia Admin
            </div>
        </div>
    </div>
</body>
</html>
