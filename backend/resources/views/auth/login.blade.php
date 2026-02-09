<x-guest-layout>
    {{-- Session Status --}}
    <x-auth-session-status class="mb-4 text-xs" :status="session('status')" />

    <h1 class="text-xl font-semibold text-gray-900">
        Connexion à Omersia Admin
    </h1>
    <p class="mt-1 text-xs text-gray-500">
        Accédez à votre espace d’administration sécurisé.
    </p>

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-xs text-gray-700" />
            <x-text-input
                id="email"
                class="mt-1 block w-full text-sm border-gray-300 rounded-md focus:ring-black focus:border-black"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
        </div>

        {{-- Password --}}
        <div>
            <x-input-label for="password" :value="__('Mot de passe')" class="text-xs text-gray-700" />
            <x-text-input
                id="password"
                class="mt-1 block w-full text-sm border-gray-300 rounded-md focus:ring-black focus:border-black"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs" />
        </div>

        {{-- Remember + Forgot --}}
        <div class="flex items-center justify-between mt-1">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 text-black focus:ring-black"
                    name="remember"
                >
                <span class="text-xs text-gray-600">
                    {{ __('Se souvenir de moi') }}
                </span>
            </label>

            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    class="text-xs text-gray-500 hover:text-gray-900"
                >
                    {{ __('Mot de passe oublié ?') }}
                </a>
            @endif
        </div>

        {{-- Actions --}}
        <div class="mt-4">
            <button
                type="submit"
                class="w-full inline-flex items-center justify-center px-4 py-2.5
                       bg-black text-white text-xs font-medium rounded-full
                       shadow-sm hover:bg-neutral-900 transition"
            >
                {{ __('Se connecter') }}
            </button>
        </div>

        @if (Route::has('register'))
            <div class="mt-3 text-xs text-gray-500 text-center">
                {{ __("Besoin d'un accès pour un membre de l’équipe ?") }}
                <a href="{{ route('register') }}" class="text-gray-800 font-medium hover:underline">
                    {{ __('Inviter un membre') }}
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>
