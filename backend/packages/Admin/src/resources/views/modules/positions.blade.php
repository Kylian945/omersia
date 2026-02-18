@extends('admin::layout')

@section('title', 'Positions des modules')
@section('page-title', 'Positions des modules')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                <x-lucide-layout-grid class="w-3 h-3" />
                <span class="font-semibold text-sm">Positions des modules</span>
            </div>
            <div class="text-xs text-gray-500">Gérez les positions et l'ordre d'affichage des modules dans votre storefront</div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.modules.index') }}"
                class="text-xs inline-flex items-center font-semibold gap-2 px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                <x-lucide-arrow-left class="w-3 h-3" />
                Retour aux modules
            </a>
        </div>
    </div>

    <!-- Barres de recherche -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label for="search-hook" class="block text-xs font-medium text-gray-700 mb-1">Rechercher par hook</label>
            <div class="relative">
                <input type="text" id="search-hook" placeholder="Rechercher un hook..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none pl-9">
                <x-lucide-search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            </div>
        </div>
        <div>
            <label for="search-module" class="block text-xs font-medium text-gray-700 mb-1">Rechercher par module</label>
            <div class="relative">
                <input type="text" id="search-module" placeholder="Rechercher un module..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none pl-9">
                <x-lucide-search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            </div>
        </div>
    </div>


    @if(empty($hooksByPosition))
        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
            <x-lucide-inbox class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900">Aucun hook de module</h3>
            <p class="mt-1 text-sm text-gray-500">Uploadez un module avec des hooks pour commencer.</p>
            <div class="mt-6">
                <a href="{{ route('admin.modules.upload') }}"
                    class="inline-flex items-center rounded-md bg-black px-3 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                    <x-lucide-upload class="mr-2 h-4 w-4" />
                    Importer un module
                </a>
            </div>
        </div>
    @else
        <div class="space-y-6">
            @foreach($hooksByPosition as $hookName => $hooks)
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden"
                    data-hook-section
                    data-hook-name="{{ $hookName }}"
                    data-hook-label="{{ $positionLabels[$hookName] ?? $hookName }}">
                    <div class="bg-gray-50 border-b border-gray-200 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    {{ $positionLabels[$hookName] ?? $hookName }}
                                </h3>
                                {{-- <p class="text-xs text-gray-500 mt-0.5">{{ $hookName }}</p> --}}
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                                    {{ count($hooks) }} module(s)
                                </span>
                                <button onclick="document.getElementById('addModuleModal_{{ $loop->index }}').showModal()"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-black hover:bg-gray-100 rounded-md">
                                    <x-lucide-plus class="w-3 h-3" />
                                    Ajouter un module
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @foreach($hooks as $hook)
                            <div class="px-4 py-3 hover:bg-gray-50 transition" x-data="{ editing: false }"
                                data-module-item
                                data-module-name="{{ $hook->module->name ?? $hook->module_slug }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 flex-1">
                                        <!-- Drag handle -->
                                        <div class="cursor-move text-gray-400 hover:text-gray-600">
                                            <x-lucide-grip-vertical class="w-4 h-4" />
                                        </div>

                                        <!-- Module info -->
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ $hook->module->name ?? $hook->module_slug }}
                                                </span>
                                                @if(!$hook->is_active)
                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                                        Désactivé
                                                    </span>
                                                @endif
                                            </div>
                                            {{-- <div class="text-xs text-gray-500 mt-0.5">
                                                <span class="font-mono">{{ $hook->component_path }}</span>
                                                @if($hook->condition)
                                                    <span class="mx-1">•</span>
                                                    <span class="italic">Condition: {{ $hook->condition }}</span>
                                                @endif
                                            </div> --}}
                                        </div>

                                        <!-- Priority -->
                                        <div class="flex items-center gap-2">
                                            <template x-if="!editing">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-500">Priorité:</span>
                                                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                                                        {{ $hook->priority }}
                                                    </span>
                                                    <button @click="editing = true" class="text-gray-400 hover:text-gray-600">
                                                        <x-lucide-pencil class="w-3 h-3" />
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="editing">
                                                <form action="{{ route('admin.modules.positions.update-priority', $hook->id) }}" method="POST" class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="number" name="priority" value="{{ $hook->priority }}" min="0" max="999"
                                                        class="w-16 rounded border border-gray-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none">
                                                    <button type="submit" class="text-green-600 hover:text-green-700">
                                                        <x-lucide-check class="w-4 h-4" />
                                                    </button>
                                                    <button type="button" @click="editing = false" class="text-gray-400 hover:text-gray-600">
                                                        <x-lucide-x class="w-4 h-4" />
                                                    </button>
                                                </form>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-2 ml-4">
                                        <!-- Toggle active -->
                                        <form action="{{ route('admin.modules.positions.toggle', $hook->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $hook->is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                                @if($hook->is_active)
                                                    <x-lucide-eye class="w-3 h-3 mr-1" />
                                                    Actif
                                                @else
                                                    <x-lucide-eye-off class="w-3 h-3 mr-1" />
                                                    Inactif
                                                @endif
                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <button onclick="document.getElementById('deleteModal_{{ $hook->id }}').showModal()"
                                            class="text-gray-600 hover:text-gray-700">
                                            <x-lucide-trash-2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Modal de confirmation de suppression -->
                                <dialog id="deleteModal_{{ $hook->id }}" class="rounded-lg shadow-xl backdrop:bg-black/50 p-0 max-w-md w-full">
                                    <div class="bg-white rounded-lg">
                                        <div class="flex items-center justify-between p-4 border-b border-gray-200">
                                            <h3 class="text-sm font-semibold text-gray-900">Supprimer ce hook</h3>
                                            <button onclick="document.getElementById('deleteModal_{{ $hook->id }}').close()" class="text-gray-400 hover:text-gray-600">
                                                <x-lucide-x class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div class="p-4">
                                            <p class="text-sm text-gray-600">
                                                Êtes-vous sûr de vouloir supprimer le module <strong>{{ $hook->module->name ?? $hook->module_slug }}</strong> de ce hook ?
                                                Cette action est irréversible.
                                            </p>
                                        </div>
                                        <div class="flex items-center justify-end gap-2 p-4 border-t border-gray-200">
                                            <button type="button" onclick="document.getElementById('deleteModal_{{ $hook->id }}').close()"
                                                class="px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                                                Annuler
                                            </button>
                                            <form action="{{ route('admin.modules.positions.destroy', $hook->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-2 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </dialog>
                            </div>
                        @endforeach
                    </div>

                    <!-- Modal pour ajouter un module -->
                    <dialog id="addModuleModal_{{ $loop->index }}" class="rounded-lg shadow-xl backdrop:bg-black/50 p-0 max-w-md w-full">
                        <div class="bg-white rounded-lg">
                            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-900">Ajouter un module à {{ $positionLabels[$hookName] ?? $hookName }}</h3>
                                <button onclick="document.getElementById('addModuleModal_{{ $loop->index }}').close()" class="text-gray-400 hover:text-gray-600">
                                    <x-lucide-x class="w-4 h-4" />
                                </button>
                            </div>
                            <form action="{{ route('admin.modules.positions.assign') }}" method="POST" class="p-4 space-y-4">
                                @csrf
                                <input type="hidden" name="hook_name" value="{{ $hookName }}">

                                <div>
                                    <label for="module_slug_{{ $loop->index }}" class="block text-xs font-medium text-gray-700 mb-1">Sélectionner un module</label>
                                    <select name="module_slug" id="module_slug_{{ $loop->index }}" required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                        <option value="">-- Choisir un module --</option>
                                        @foreach($activeModules as $slug => $name)
                                            <option value="{{ $slug }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="priority_{{ $loop->index }}" class="block text-xs font-medium text-gray-700 mb-1">Priorité</label>
                                    <input type="number" name="priority" id="priority_{{ $loop->index }}" value="10" min="0" max="999"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                    <p class="text-xs text-gray-500 mt-1">Plus la valeur est basse, plus le module sera affiché en premier</p>
                                </div>

                                <div class="flex items-center justify-end gap-2 pt-2">
                                    <button type="button" onclick="document.getElementById('addModuleModal_{{ $loop->index }}').close()"
                                        class="px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                                        Annuler
                                    </button>
                                    <button type="submit"
                                        class="px-3 py-2 text-xs font-medium text-white bg-black hover:bg-slate-800 rounded-md">
                                        Ajouter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </dialog>
                </div>
            @endforeach
        </div>

        <!-- Info box -->
        {{-- <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="flex gap-3">
                <x-lucide-info class="h-5 w-5 text-blue-500 flex-shrink-0" />
                <div class="text-sm text-blue-800">
                    <p class="font-semibold">Comment utiliser les positions ?</p>
                    <ul class="mt-2 space-y-1 list-disc list-inside text-xs">
                        <li>Les modules sont affichés dans l'ordre de <strong>priorité</strong> (plus bas = plus prioritaire)</li>
                        <li>Vous pouvez activer/désactiver un module sans le supprimer</li>
                        <li>Les <strong>conditions</strong> déterminent quand le module s'affiche (ex: seulement pour Colissimo Point Relais)</li>
                        <li>Plusieurs modules peuvent s'afficher à la même position si leurs conditions sont différentes</li>
                    </ul>
                </div>
            </div>
        </div> --}}
    @endif

    @vite(['packages/Admin/src/resources/js/modules/positions.js'])
@endsection
