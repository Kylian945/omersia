<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900">
        Réinitialiser votre mot de passe
    </h1>
    <p class="mt-1 text-xs text-gray-500">
        Indiquez votre adresse email, nous vous enverrons un lien sécurisé
        pour définir un nouveau mot de passe.
    </p>

    {{-- Session Status --}}
    <x-auth-session-status class="mb-4 text-xs" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-4 space-y-4">
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
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
        </div>

        <div class="mt-4">
            <button
                type="submit"
                class="w-full inline-flex items-center justify-center px-4 py-2.5
                       bg-black text-white text-xs font-medium rounded-full
                       shadow-sm hover:bg-neutral-900 transition"
            >
                {{ __('Envoyer le lien de réinitialisation') }}
            </button>
        </div>
    </form>
</x-guest-layout>
