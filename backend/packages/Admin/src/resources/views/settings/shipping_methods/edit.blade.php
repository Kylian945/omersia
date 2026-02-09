@extends('admin::settings.layout')

@section('title', "Modifier {$method->name}")
@section('page-title', "Modifier {$method->name}")

@section('settings-content')
<div class="space-y-4">
    <div class="flex items-center gap-2 mb-2">
        <x-lucide-truck class="w-4 h-4" />
        <h1 class="text-base font-semibold">Modifier une méthode de livraison</h1>
    </div>

    <div class="rounded-2xl bg-white border border-black/5 shadow-sm p-4">
        @include('admin::settings.shipping_methods._form', [
            'method'      => $method,
            'action'      => route('admin.settings.shipping_methods.update', $method),
            'submitLabel' => 'Enregistrer',
        ])
    </div>

    @include('admin::settings.shipping_methods._configuration')
</div>
@endsection
