@extends('admin::layout')

@section('title', 'Historique des versions')
@section('page-title', 'Historique des versions')

@section('content')
    @php
        $pageTitle = $translation->title ?? 'Sans titre';
    @endphp

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                    <x-lucide-history class="w-3 h-3" />
                    Versions de la page
                </div>
                <div class="text-xs text-gray-500">
                    {{ $pageTitle }} (locale : {{ $locale }})
                </div>
            </div>

            <a href="{{ route('pages.edit', $page) }}"
                class="rounded-lg border border-gray-200 px-4 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Retour à l'édition
            </a>
        </div>

        <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
            <table class="w-full text-xs text-gray-700">
                <thead class="bg-[#f9fafb] border-b border-gray-100">
                    <tr>
                        <th class="py-2 px-3 text-left font-medium text-gray-500">Date</th>
                        <th class="py-2 px-3 text-left font-medium text-gray-500">Label</th>
                        <th class="py-2 px-3 text-left font-medium text-gray-500">Créé par</th>
                        <th class="py-2 px-3 text-right font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($versions as $version)
                        @php
                            $diff = $diffsByVersionId[$version->id] ?? ['added' => [], 'removed' => [], 'changed' => [], 'has_changes' => false];
                            $changeCount = count($diff['added']) + count($diff['removed']) + count($diff['changed']);
                        @endphp
                        <tr class="border-b border-gray-50">
                            <td class="py-2 px-3 align-top text-gray-500">
                                {{ $version->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="py-2 px-3 align-top">
                                <div class="font-medium text-gray-900">
                                    {{ $version->label ?: 'Version sans label' }}
                                </div>
                                <div class="text-xxxs text-gray-500 mt-0.5">
                                    {{ $changeCount }} changement(s) vs version actuelle
                                </div>
                            </td>
                            <td class="py-2 px-3 align-top text-gray-500">
                                {{ $version->creator?->name ?? $version->creator?->email ?? 'Système' }}
                            </td>
                            <td class="py-2 px-3 align-top">
                                <div class="flex items-center justify-end gap-1.5">
                                    <details class="group">
                                        <summary class="list-none cursor-pointer rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                                            Voir le diff
                                        </summary>
                                        <div class="mt-2 w-[700px] max-w-[85vw] rounded-xl border border-gray-200 bg-white p-3 shadow-lg">
                                            @if (! $diff['has_changes'])
                                                <p class="text-xxxs text-gray-500">Aucune différence avec la version actuelle.</p>
                                            @else
                                                @if (count($diff['changed']) > 0)
                                                    <div class="mb-3">
                                                        <p class="text-xxxs font-semibold text-gray-700 mb-1">Modifications</p>
                                                        <div class="space-y-1">
                                                            @foreach($diff['changed'] as $item)
                                                                <div class="rounded-md border border-amber-100 bg-amber-50 px-2 py-1">
                                                                    <div class="font-mono text-xxxs text-amber-800">{{ $item['path'] }}</div>
                                                                    <div class="text-xxxs text-amber-700">Avant : {{ $item['old'] }}</div>
                                                                    <div class="text-xxxs text-amber-700">Après : {{ $item['new'] }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (count($diff['added']) > 0)
                                                    <div class="mb-3">
                                                        <p class="text-xxxs font-semibold text-gray-700 mb-1">Ajouts</p>
                                                        <div class="space-y-1">
                                                            @foreach($diff['added'] as $item)
                                                                <div class="rounded-md border border-emerald-100 bg-emerald-50 px-2 py-1">
                                                                    <div class="font-mono text-xxxs text-emerald-800">{{ $item['path'] }}</div>
                                                                    <div class="text-xxxs text-emerald-700">{{ $item['value'] }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (count($diff['removed']) > 0)
                                                    <div>
                                                        <p class="text-xxxs font-semibold text-gray-700 mb-1">Suppressions</p>
                                                        <div class="space-y-1">
                                                            @foreach($diff['removed'] as $item)
                                                                <div class="rounded-md border border-red-100 bg-red-50 px-2 py-1">
                                                                    <div class="font-mono text-xxxs text-red-800">{{ $item['path'] }}</div>
                                                                    <div class="text-xxxs text-red-700">{{ $item['value'] }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </details>

                                    <button type="button"
                                        class="rounded-full border border-indigo-100 px-2 py-0.5 text-xxxs text-indigo-700 hover:bg-indigo-50"
                                        @click="$dispatch('open-modal', { name: 'restore-page-version-{{ $version->id }}' })">
                                        Restaurer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-4 px-3 text-center text-xs text-gray-500" colspan="4">
                                Aucune version enregistrée pour cette page.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-3 py-2 border-t border-gray-100">
                {{ $versions->appends(['locale' => $locale])->links() }}
            </div>
        </div>

        @foreach($versions as $version)
            <x-admin::modal name="restore-page-version-{{ $version->id }}"
                title="Restaurer cette version ?"
                description="Le contenu actuel sera sauvegardé automatiquement avant restauration."
                size="max-w-md">
                <p class="text-xs text-gray-600">
                    Voulez-vous restaurer
                    <span class="font-semibold">{{ $version->label ?: 'cette version' }}</span>
                    du {{ $version->created_at?->format('d/m/Y H:i') ?? '-' }} ?
                </p>

                <div class="flex justify-end gap-2 pt-3">
                    <button type="button"
                        class="px-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-700 hover:bg-gray-50"
                        @click="open = false">
                        Annuler
                    </button>
                    <form method="POST"
                        action="{{ route('pages.versions.restore', ['page' => $page->id, 'version' => $version->id, 'locale' => $locale]) }}">
                        @csrf
                        <button type="submit"
                            class="rounded-lg bg-black px-4 py-2 text-xs font-medium text-white hover:bg-neutral-900">
                            Confirmer la restauration
                        </button>
                    </form>
                </div>
            </x-admin::modal>
        @endforeach
    </div>
@endsection
