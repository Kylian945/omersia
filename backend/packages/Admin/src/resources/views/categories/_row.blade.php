@php
    /** @var \Omersia\Catalog\Models\Category $category */
@endphp
<tr class="border-b border-gray-50 hover:bg-[#fafafa]">
    <td class="py-2 px-3">
        <div class="flex items-center gap-2" style="padding-left: {{ $indentPx }}px">
            @if($category->image_url)
                <img src="{{ $category->image_url }}" alt="" class="w-8 h-8 object-cover rounded-lg border border-gray-200">
            @else
                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif
            <span class="font-medium text-xs text-gray-900">
                {{ $t?->name ?? 'Sans nom' }}
            </span>
        </div>
    </td>
    <td class="py-2 px-3 text-gray-500">
        {{ $t?->slug }}
    </td>
    <td class="py-2 px-3 text-gray-500 text-xxxs">
        {{ $parent?->name ?? ($level === 0 ? '-' : '') }}
    </td>
    <td class="py-2 px-3">
        @if ($category->is_active)
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xxxs text-emerald-700">
                Actif
            </span>
        @else
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xxxs text-gray-500">
                Inactif
            </span>
        @endif
    </td>
    <td class="py-2 px-3 text-right align-middle">
        <div class="flex items-center justify-end gap-1.5">
            <a href="{{ route('categories.edit', $category) }}"
                class="rounded-full border border-gray-200 px-2 py-0.5 text-xxxs text-gray-700 hover:bg-gray-50">
                Modifier
            </a>
            <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button class="rounded-full border border-red-100 px-2 py-0.5 text-xxxs text-red-500 hover:bg-red-50"
                    onclick="return confirm('Supprimer cette catégorie ?')">
                    Supprimer
                </button>
            </form>
        </div>
    </td>
</tr>
