<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900">
        Vérifiez votre adresse email
    </h1>
    <p class="mt-2 text-xs text-gray-500">
        Nous vous avons envoyé un lien de confirmation.
        Cliquez dessus pour activer votre accès à Omersia Admin.
        Si vous ne l’avez pas reçu, vous pouvez en demander un nouveau.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-4 mb-4 text-xs text-emerald-600 font-medium">
            {{ __('Un nouveau lien de vérification a été envoyé à votre adresse email.') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center justify-center px-4 py-2.5
                       bg-black text-white text-xs font-medium rounded-full
                       shadow-sm hover:bg-neutral-900 transition"
            >
                {{ __('Renvoyer l’email de vérification') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="text-xs text-gray-500 hover:text-gray-900"
            >
                {{ __('Se déconnecter') }}
            </button>
        </form>
    </div>
</x-guest-layout>
