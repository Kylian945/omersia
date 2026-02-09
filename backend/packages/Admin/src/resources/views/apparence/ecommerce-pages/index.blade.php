@extends('admin::layout')

@section('title', 'Pages E-commerce')
@section('page-title', 'Pages E-commerce')

@section('content')
<div x-data="{}">
    <div class="flex items-center justify-between mb-3">
        <div>
            <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                <x-lucide-shopping-bag class="w-3 h-3" />
                Pages E-commerce
            </div>
            <div class="text-xs text-gray-500">
                Gérez les pages de votre boutique avec le page builder
            </div>
        </div>
        <a href="{{ route('admin.apparence.ecommerce-pages.create') }}"
            class="self-start rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
            Ajouter une page
        </a>
    </div>

    <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
        <table class="w-full text-xs text-gray-700">
            <thead class="bg-[#f9fafb] border-b border-gray-100">
                <tr>
                    <th class="py-2 px-3 text-left font-medium text-gray-400">Titre</th>
                    <th class="py-2 px-3 text-left font-medium text-gray-400">Slug</th>
                    <th class="py-2 px-3 text-left font-medium text-gray-400">Type</th>
                    <th class="py-2 px-3 text-left font-medium text-gray-400">Visibilité</th>
                    <th class="py-2 px-3 text-right font-medium text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                    @php $t = $page->translations->first(); @endphp
                    <tr class="border-b border-gray-50 hover:bg-[#fafafa]">
                        <td class="py-2 px-3">
                            <div class="font-medium text-xs text-gray-900">
                                {{ $t?->title ?? 'Sans titre' }}
                            </div>
                        </td>
                        <td class="py-2 px-3 text-gray-500">
                            {{ $page->slug ?? '-' }}
                        </td>
                        <td class="py-2 px-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xxxs
                                {{ $page->type === 'home' ? 'bg-purple-50 text-purple-700' : '' }}
                                {{ $page->type === 'category' ? 'bg-blue-50 text-blue-700' : '' }}
                                {{ $page->type === 'product' ? 'bg-green-50 text-green-700' : '' }}">
                                {{ ucfirst($page->type) }}
                            </span>
                        </td>
                        <td class="py-2 px-3">
                            @if ($page->is_active)
                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xxxs text-emerald-700">
                                    Visible
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xxxs text-gray-500">
                                    Masquée
                                </span>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-right align-middle">
                            <div class="flex items-center justify-end gap-1.5">

                                <a href="{{ route('admin.apparence.ecommerce-pages.edit', $page) }}"
                                    class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                    Modifier
                                </a>
                                <button type="button"
                                        class="rounded-full border border-red-100 px-2 py-0.5 text-xxxs text-red-500 hover:bg-red-50"
                                        @click="$dispatch('open-modal', { name: 'delete-ecommerce-page-{{ $page->id }}' })">
                                    Supprimer
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="py-4 px-3 text-center text-xs text-gray-500" colspan="5">
                            Aucune page e-commerce créée. Ajoutez vos pages pour personnaliser votre boutique.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modals de confirmation de suppression --}}
    @foreach($pages as $page)
        @php $t = $page->translations->first(); @endphp
        <x-admin::modal name="delete-ecommerce-page-{{ $page->id }}"
            :title="'Supprimer la page « ' . ($t?->title ?? 'Sans titre') . ' » ?'"
            description="Cette action est définitive et ne peut pas être annulée."
            size="max-w-md">
            <p class="text-xs text-gray-600">
                Voulez-vous vraiment supprimer la page
                <span class="font-semibold">{{ $t?->title ?? 'Sans titre' }}</span> ?
            </p>

            <div class="flex justify-end gap-2 pt-3">
                <button type="button"
                    class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                    @click="open = false">
                    Annuler
                </button>

                <form action="{{ route('admin.apparence.ecommerce-pages.destroy', $page) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-xs font-medium text-white hover:bg-neutral-900">
                        <x-lucide-trash-2 class="h-3 w-3" />
                        Confirmer la suppression
                    </button>
                </form>
            </div>
        </x-admin::modal>
    @endforeach
</div>
@endsection
