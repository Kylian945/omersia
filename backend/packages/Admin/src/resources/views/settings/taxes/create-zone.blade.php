@extends('admin::settings.layout')

@section('title', 'Nouvelle zone de taxation')
@section('page-title', 'Nouvelle zone de taxation')

@section('settings-content')
<div class="space-y-4">
    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('admin.settings.taxes.index') }}"
           class="text-xs text-neutral-600 hover:text-black">
            ← Retour aux taxes
        </a>
    </div>

    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-6">
        <form method="POST" action="{{ route('admin.settings.taxes.zones.store') }}">
            @csrf

            @include('admin::settings.taxes._zone-form')

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center rounded-lg bg-neutral-900 text-white text-xs px-4 py-2 hover:bg-black">
                    Créer la zone
                </button>
                <a href="{{ route('admin.settings.taxes.index') }}"
                   class="inline-flex items-center rounded-lg border border-neutral-200 text-neutral-700 text-xs px-4 py-2 hover:bg-neutral-50">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
