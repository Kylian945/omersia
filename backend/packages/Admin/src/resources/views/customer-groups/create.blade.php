@extends('admin::layout')

@section('title', 'Créer un groupe de clients')
@section('page-title', 'Créer un groupe de clients')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-sm font-semibold flex items-center gap-2">
                <x-lucide-users class="w-4 h-4" />
                Nouveau groupe de clients
            </h1>
            <p class="text-xs text-neutral-500">
                Créez un segment de clients pour cibler vos réductions et campagnes.
            </p>
        </div>

        <a href="{{ route('customer-groups.index') }}"
           class="px-3 py-1.5 rounded-md border text-xs">
            Retour
        </a>
    </div>

    <form method="POST" action="{{ route('customer-groups.store') }}" class="space-y-4">
        @csrf

        @include('admin::customer-groups.partials.form', [
            'group' => null,
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
