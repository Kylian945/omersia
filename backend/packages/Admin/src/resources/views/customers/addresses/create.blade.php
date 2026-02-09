@extends('admin::layout')

@section('title', 'Nouvelle adresse')
@section('page-title', 'Nouvelle adresse')

@section('content')
    <div class="mb-4">
        <div class="text-xs text-slate-500 mb-1">
            Client #{{ $customer->id }} · {{ $customer->fullname }}
        </div>
        <div class="text-sm font-semibold text-slate-800">
            Ajouter une adresse
        </div>
    </div>

    <form method="POST" action="{{ route('admin.customers.addresses.store', $customer) }}"
          class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
        @include('admin::customers.addresses._form', ['address' => $address])
    </form>
@endsection
