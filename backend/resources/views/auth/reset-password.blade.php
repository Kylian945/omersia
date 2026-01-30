<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900">
        Nouveau mot de passe
    </h1>
    <p class="mt-1 text-xs text-gray-500">
        Choisissez un nouveau mot de passe pour sécuriser votre accès Omersia Admin.
    </p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-xs text-gray-700" />
            <x-text-input
                id="email"
                class="mt-1 block w-full text-sm border-gray-300 rounded-md focus:ring-black focus:border-black"
                type="email"
                name="email"
                :value="old('email', $request->email)"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
        </div>

        {{-- Password --}}
        <div>
            <x-input-label for="password" :value="__('Nouveau mot de passe')" class="text-xs text-gray-700" />
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

        <div class="mt-4">
            <button
                type="submit"
                class="w-full inline-flex items-center justify-center px-4 py-2.5
                       bg-black text-white text-xs font-medium rounded-full
                       shadow-sm hover:bg-neutral-900 transition"
            >
                {{ __('Enregistrer le nouveau mot de passe') }}
            </button>
        </div>
    </form>
</x-guest-layout>
