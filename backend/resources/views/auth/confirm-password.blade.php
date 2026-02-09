<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900">
        Confirmer votre mot de passe
    </h1>
    <p class="mt-1 text-xs text-gray-500">
        Pour continuer sur cette section sécurisée, confirmez votre mot de passe Omersia Admin.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

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

        <div class="mt-4">
            <button
                type="submit"
                class="w-full inline-flex items-center justify-center px-4 py-2.5
                       bg-black text-white text-xs font-medium rounded-full
                       shadow-sm hover:bg-neutral-900 transition"
            >
                {{ __('Confirmer') }}
            </button>
        </div>
    </form>
</x-guest-layout>
