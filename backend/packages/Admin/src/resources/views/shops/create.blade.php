@extends('admin::layout')
@section('title', 'Créer une boutique')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
  <!-- Page Header -->
  <div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-900 mb-2">Créer votre boutique</h1>
    <p class="text-sm text-gray-600">
      Aucune boutique n'est encore configurée. Commencez par créer votre boutique principale.
    </p>
  </div>

  <form action="{{ route('admin.shops.store') }}" method="POST">
    @csrf

    <!-- Card Container -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <!-- Card Header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-base font-semibold text-gray-900">Informations de la boutique</h2>
      </div>

      <!-- Card Body -->
      <div class="px-6 py-5 space-y-5">
        <!-- Nom de la boutique -->
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">
            Nom de la boutique
          </label>
          <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name') }}"
            class="w-full px-3 py-2 border {{ $errors->has('name') ? 'border-red-500' : 'border-gray-300' }} rounded-md shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors"
            required
            autocomplete="off"
          >
          @error('name')
            <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p>
          @enderror
        </div>

        <!-- Code technique -->
        <div>
          <label for="code" class="block text-sm font-medium text-gray-700 mb-1.5">
            Code technique
          </label>
          <input
            type="text"
            id="code"
            name="code"
            value="{{ old('code') }}"
            class="w-full px-3 py-2 border {{ $errors->has('code') ? 'border-red-500' : 'border-gray-300' }} rounded-md shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors"
            placeholder="default"
            required
            autocomplete="off"
          >
          <p class="text-xs text-gray-500 mt-1.5">
            Identifiant unique utilisé en interne. Lettres, chiffres et tirets uniquement.
          </p>
          @error('code')
            <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p>
          @enderror
        </div>

        <!-- Domaine principal -->
        <div>
          <label for="domain" class="block text-sm font-medium text-gray-700 mb-1.5">
            Domaine principal
          </label>
          <input
            type="text"
            id="domain"
            name="domain"
            value="{{ old('domain') }}"
            class="w-full px-3 py-2 border {{ $errors->has('domain') ? 'border-red-500' : 'border-gray-300' }} rounded-md shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors"
            placeholder="myshop.test"
            required
            autocomplete="off"
          >
          @error('domain')
            <p class="text-red-600 text-sm mt-1.5">{{ $message }}</p>
          @enderror
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="mt-6 flex items-center justify-end gap-3">
      <button
        type="submit"
        class="inline-flex items-center px-4 py-2.5 bg-black text-white text-sm font-medium rounded-md shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors"
      >
        Créer la boutique
      </button>
    </div>
  </form>
</div>
@endsection
