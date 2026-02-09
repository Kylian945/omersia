@extends('admin::layout')

@section('title', 'Modifier un groupe de clients')
@section('page-title', 'Modifier un groupe de clients')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-sm font-semibold flex items-center gap-2">
                <x-lucide-users class="w-4 h-4" />
                Modifier le groupe
            </h1>
            <p class="text-xs text-neutral-500">
                Gérez les informations et les clients associés à ce groupe.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('customer-groups.index') }}"
               class="px-3 py-1.5 rounded-md border text-xs">
                Retour
            </a>

            {{-- Bouton supprimer (form séparé pour éviter les forms imbriqués) --}}
            <form method="POST"
                  action="{{ route('customer-groups.destroy', $group) }}"
                  onsubmit="return confirm('Supprimer ce groupe de clients ?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-3 py-1.5 rounded-md border border-red-200 text-xxs text-red-600">
                    Supprimer
                </button>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('customer-groups.update', $group) }}" class="space-y-4">
        @csrf
        @method('PUT')

        @include('admin::customer-groups.partials.form', [
            'group' => $group,
            'customers' => $customers ?? collect(),
        ])

        <div class="flex justify-end gap-2">
            <a href="{{ route('customer-groups.index') }}"
               class="px-3 py-1.5 rounded-md border text-xs">
                Annuler
            </a>
            <button type="submit"
                    class="px-3 py-1.5 rounded-md bg-black text-white text-xs">
                Enregistrer
            </button>
        </div>
    </form>

</div>
@endsection
