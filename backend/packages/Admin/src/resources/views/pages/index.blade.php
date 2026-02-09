@extends('admin::layout')

@section('title', 'Pages')
@section('page-title', 'Pages')

@section('content')
<div x-data="{}">
    <div class="flex items-center justify-between mb-3">
        <div>
            <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                <x-lucide-file-text class="w-3 h-3" />
                Pages
            </div>
            <div class="text-xs text-gray-500">
                Gérez vos pages éditoriales : À propos, FAQ, CGV, etc.
            </div>
        </div>
        <a href="{{ route('pages.create') }}"
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
                    <th class="py-2 px-3 text-left font-medium text-gray-400">Home</th>
                    <th class="py-2 px-3 text-right font-medium text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                    @php $t = $page->translation('fr'); @endphp
                    <tr class="border-b border-gray-50 hover:bg-[#fafafa]">
                        <td class="py-2 px-3">
                            <div class="font-medium text-xs text-gray-900">
                                {{ $t?->title ?? 'Sans titre' }}
                            </div>
                        </td>
                        <td class="py-2 px-3 text-gray-500">
                            {{ $t?->slug }}
                        </td>
                        <td class="py-2 px-3 text-gray-500 text-xxxs">
                            {{ $page->type }}
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
                        <td class="py-2 px-3">
                            @if ($page->is_home)
                                <span class="text-xs">🏠</span>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-right align-middle">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('pages.edit', $page) }}"
                                    class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                    Modifier
                                </a>
                                <button type="button"
                                        class="rounded-full border border-red-100 px-2 py-0.5 text-xxxs text-red-500 hover:bg-red-50"
                                        @click="$dispatch('open-modal', { name: 'delete-page-{{ $page->id }}' })">
                                    Supprimer
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="py-4 px-3 text-center text-xs text-gray-500" colspan="6">
                            Aucune page créée. Ajoutez vos pages de contenu pour compléter votre boutique.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-3 py-2 border-t border-gray-100">
            {{ $pages->links() }}
        </div>
    </div>

    {{-- Modals de confirmation de suppression --}}
    @foreach($pages as $page)
        @php $t = $page->translation('fr'); @endphp
        <x-admin::modal name="delete-page-{{ $page->id }}"
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

                <form action="{{ route('pages.destroy', $page) }}" method="POST">
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
