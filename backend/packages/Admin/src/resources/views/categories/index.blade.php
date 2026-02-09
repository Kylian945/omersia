@extends('admin::layout')

@section('title', 'Catégories')
@section('page-title', 'Catégories')

@section('content')
    <div class="flex items-center justify-between mb-3">
        <div>
            <div class="text-sm font-semibold text-gray-800 flex items-baseline gap-1.5">
                <x-lucide-folders class="w-3 h-3" />
                Catégories
            </div>
            <div class="text-xs text-gray-500">
                Organisez votre catalogue en sections claires.
            </div>
        </div>
        <a href="{{ route('categories.create') }}"
            class="rounded-lg bg-[#111827] px-4 py-1.5 text-xs font-semibold text-white hover:bg-black">
            Ajouter une catégorie
        </a>
    </div>

    @php
        // On regroupe les catégories par parent_id pour créer l'arbo
// $categories doit être une Collection de toutes les catégories
$byParent = $categories->groupBy('parent_id');

$renderRows = function ($parentId, $level = 0) use (&$renderRows, $byParent) {
    $rows = '';

    if (!isset($byParent[$parentId])) {
        return $rows;
    }

    foreach ($byParent[$parentId] as $category) {
        $t = $category->translation('fr');
        $parent = $category->parent?->translation('fr');

        // indentation visuelle selon le niveau
        $indentPx = 4 + $level * 10 * 2; // ajuste si tu veux
        $name = $t?->name ?? 'Sans nom';

        $rows .= view('admin::categories._row', [
            'category' => $category,
            't' => $t,
            'parent' => $parent,
            'level' => $level,
            'indentPx' => $indentPx,
                ])->render();

                // récursif : on affiche les enfants
                $rows .= $renderRows($category->id, $level + 1);
            }

            return $rows;
        };
    @endphp

    <div class="rounded-2xl bg-white border border-black/5 shadow-sm overflow-hidden">
        <table class="w-full text-xs text-gray-700">
            <thead class="bg-[#f9fafb] border-b border-gray-100">
                <tr>
                    <th class="py-2 px-3 text-left font-medium text-gray-400">Nom</th>
                    <th class="py-2 px-3 text-left font-medium text-gray-400">Slug</th>
                    <th class="py-2 px-3 text-left font-medium text-gray-400">Parent</th>
                    <th class="py-2 px-3 text-left font-medium text-gray-400">Actif</th>
                    <th class="py-2 px-3 text-right font-medium text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @if ($categories->isEmpty())
                    <tr>
                        <td class="py-4 px-3 text-center text-xs text-gray-500" colspan="5">
                            Aucune catégorie pour le moment. Créez-en une pour structurer vos produits.
                        </td>
                    </tr>
                @else
                    {!! $renderRows(null, 0) !!}
                @endif
            </tbody>
        </table>
    </div>
@endsection
