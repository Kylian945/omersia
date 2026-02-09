@extends('admin::layout')

@section('title', 'Menu')
@section('page-title', 'Menu')

@section('content')
    <div x-data="{}">
        <div class="flex items-center justify-between mb-3">
            <div>
                <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                    <x-lucide-list class="w-3 h-3" />
                    Menu
                </div>
                <div class="text-xs text-gray-500">
                    Gérez les liens affichés dans votre navigation : catégories, pages CMS, liens personnalisés, textes.
                </div>
            </div>

            <div class="flex items-center gap-2">

                <button type="button" x-data
                    @click="window.dispatchEvent(new CustomEvent('open-modal', { detail: { name: 'create-menu' } }))"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-1.5 font-semibold text-xs text-black hover:bg-gray-50">
                    Ajouter un menu
                </button>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
            <div class="w-full flex gap-2 items-center px-4 py-2">
                @if (isset($menus) && $menus->count() > 0)

                    <form method="GET" class="flex-1 flex items-center gap-2">
                        <label class="text-xs text-gray-600">
                            Sélectionner un menu :
                        </label>
                        <select name="menu"
                            class="flex-1 rounded-xl border border-gray-200 pl-2 pr-6 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-gray-900"
                            onchange="this.form.submit()">
                            @foreach ($menus as $m)
                                <option value="{{ $m->slug }}" @selected($m->id === $menu->id)>
                                    {{ $m->name }} ({{ $m->location }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('admin.apparence.menus.create', ['menu' => $menu->slug]) }}"
                        class="rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
                        Ajouter un élément
                    </a>
                @endif

            </div>
            <table class="w-full text-xs text-gray-700">
                <thead class="bg-[#f9fafb] border-b border-t border-gray-200">
                    <tr>
                        <th class="py-2 px-3 text-left font-medium text-gray-600">Label</th>
                        <th class="py-2 px-3 text-left font-medium text-gray-600">Type</th>
                        <th class="py-2 px-3 text-left font-medium text-gray-600">Cible</th>
                        <th class="py-2 px-3 text-left font-medium text-gray-600">Position</th>
                        <th class="py-2 px-3 text-left font-medium text-gray-600">Visibilité</th>
                        <th class="py-2 px-3 text-right font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menuItems as $item)
                        <tr class="border-b border-gray-50 hover:bg-[#fafafa]">
                            <td class="py-2 px-3">
                                <div class="font-medium text-xs text-gray-900">
                                    {{ $item->label ?? 'Sans label' }}
                                </div>
                            </td>
                            <td class="py-2 px-3 text-xxxs text-gray-500">
                                @if ($item->type === 'category')
                                    Catégorie
                                @elseif($item->type === 'cms_page')
                                    Page CMS
                                @elseif($item->type === 'link')
                                    Lien spécifique
                                @elseif($item->type === 'text')
                                    Texte seul
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-2 px-3 text-xxxs text-gray-500">
                                @if ($item->type === 'category' && $item->category)
                                    {{ $item->url }}
                                @elseif($item->type === 'cms_page')
                                    {{ $item->url }}
                                @elseif($item->type === 'link')
                                    {{ $item->url }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-2 px-3 text-xxxs text-gray-500">
                                {{ $item->position ?? '-' }}
                            </td>
                            <td class="py-2 px-3">
                                @if ($item->is_active)
                                    <span
                                        class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xxxs text-emerald-700">
                                        Visible
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xxxs text-gray-500">
                                        Masqué
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-right align-middle">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.apparence.menus.edit', $item) }}"
                                        class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                        Modifier
                                    </a>
                                    <button type="button"
                                            class="rounded-full border border-red-100 px-2 py-0.5 text-xxxs text-red-500 hover:bg-red-50"
                                            @click="$dispatch('open-modal', { name: 'delete-menu-item-{{ $item->id }}' })">
                                        Supprimer
                                    </button>
                                </div>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-4 px-3 text-center text-xs text-gray-500" colspan="6">
                                Aucun élément de menu pour ce menu. Ajoutez vos liens pour construire votre navigation.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modals de confirmation de suppression --}}
        @foreach($menuItems as $item)
            <x-admin::modal name="delete-menu-item-{{ $item->id }}"
                :title="'Supprimer l\'élément « ' . $item->label . ' » ?'"
                description="Cette action est définitive et ne peut pas être annulée."
                size="max-w-md">
                <p class="text-xs text-gray-600">
                    Voulez-vous vraiment supprimer l'élément de menu
                    <span class="font-semibold">{{ $item->label }}</span> ?
                </p>

                <div class="flex justify-end gap-2 pt-3">
                    <button type="button"
                        class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                        @click="open = false">
                        Annuler
                    </button>

                    <form action="{{ route('admin.apparence.menus.destroy', $item) }}" method="POST">
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

        {{-- Modal de création de menu --}}
        <x-admin::modal name="create-menu" title="Nouveau menu"
            description="Créez un menu distinct pour votre header, footer ou autre zone." size="max-w-sm">
            <form action="{{ route('admin.apparence.menus.store-base') }}" method="POST" class="space-y-2">
                @csrf

                <div>
                    <label class="block text-xxxs font-medium text-gray-700 mb-0.5">
                        Nom du menu
                    </label>
                    <input type="text" name="name"
                        class="w-full rounded-xl border border-gray-200 px-2 py-1 text-xxxs focus:outline-none focus:ring-1 focus:ring-gray-900"
                        placeholder="Ex : Menu footer" required>
                </div>

                <div>
                    <label class="block text-xxxs font-medium text-gray-700 mb-0.5">
                        Slug
                    </label>
                    <input type="text" name="slug"
                        class="w-full rounded-xl border border-gray-200 px-2 py-1 text-xxxs focus:outline-none focus:ring-1 focus:ring-gray-900"
                        placeholder="Ex : footer" required>
                </div>

                <div>
                    <label class="block text-xxxs font-medium text-gray-700 mb-0.5">
                        Emplacement / Location
                    </label>
                    <input type="text" name="location"
                        class="w-full rounded-xl border border-gray-200 px-2 py-1 text-xxxs focus:outline-none focus:ring-1 focus:ring-gray-900"
                        placeholder="Ex : footer" required>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button"
                        class="px-4 py-1.5 font-semibold rounded-lg border border-gray-200 text-xxxs text-gray-600 hover:bg-gray-50"
                        @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: { name: 'create-menu' } }))">
                        Annuler
                    </button>
                    <button type="submit"
                        class="px-4 py-1.5 rounded-lg font-semibold bg-[#111827] text-xs text-white hover:bg-black">
                        Créer le menu
                    </button>
                </div>
            </form>
        </x-admin::modal>
    </div>
@endsection
