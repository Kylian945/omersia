<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900">
        Inviter un membre de l’équipe
    </h1>
    <p class="mt-1 text-xs text-gray-500">
        Créez un accès administrateur ou éditeur pour votre back-office Omersia.
    </p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        {{-- firstname --}}
        <div>
            <x-input-label for="firstname" :value="__('Prénom')" class="text-xs text-gray-700" />
            <x-text-input
                id="firstname"
                class="mt-1 block w-full text-sm border-gray-300 rounded-md focus:ring-black focus:border-black"
                type="text"
                name="firstname"
                :value="old('firstname')"
                required
                autofocus
                autocomplete="firstname"
            />
            <x-input-error :messages="$errors->get('firstname')" class="mt-1 text-xs" />
        </div>

        {{-- Name --}}
        <div>
            <x-input-label for="lastname" :value="__('Nom')" class="text-xs text-gray-700" />
            <x-text-input
                id="lastname"
                class="mt-1 block w-full text-sm border-gray-300 rounded-md focus:ring-black focus:border-black"
                type="text"
                name="lastname"
                :value="old('lastname')"
                required
                autofocus
                autocomplete="lastname"
            />
            <x-input-error :messages="$errors->get('lastname')" class="mt-1 text-xs" />
        </div>

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email professionnel')" class="text-xs text-gray-700" />
            <x-text-input
                id="email"
                class="mt-1 block w-full text-sm border-gray-300 rounded-md focus:ring-black focus:border-black"
                type="email"
                name="email"
                :value="old('email')"
                required
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
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs" />
        </div>

        {{-- Confirm --}}
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" class="text-xs text-gray-700" />
            <x-text-input
                id="password_confirmation"
                class="mt-1 block w-full text-sm border-gray-300 rounded-md focus:ring-black focus:border-black"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs" />
        </div>

        {{-- Actions --}}
        <div class="mt-4 flex items-center justify-between gap-3">
            <a
                href="{{ route('login') }}"
                class="text-xs text-gray-500 hover:text-gray-900"
            >
                {{ __('Déjà un compte ? Se connecter') }}
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center px-4 py-2.5
                       bg-black text-white text-xs font-medium rounded-full
                       shadow-sm hover:bg-neutral-900 transition"
            >
                {{ __('Créer le compte') }}
            </button>
        </div>
    </form>
</x-guest-layout>
