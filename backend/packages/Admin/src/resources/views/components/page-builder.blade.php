@props([
    'field' => 'content_json',
    'value' => '[]',
])

@php
    // $value peut être un array (edit) ou une string (old input)
    $initial = is_string($value) ? $value : json_encode($value);
@endphp

<div
    x-data='pageBuilder(@json($initial))'
    x-init="sync()"
    class="space-y-4"
>
    {{-- Header + bouton ajout --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="text-xs font-semibold text-gray-800">Builder de contenu</div>
            <div class="text-xxxs text-gray-500">
                Ajoutez des blocs et visualisez le rendu final en temps réel.
            </div>
        </div>
        <div class="flex items-center gap-2">
            <select x-model="newType"
                    class="rounded-full border border-gray-200 bg-white pl-2 pr-6 py-1 text-xxxs">
                <option value="">+ Ajouter un bloc</option>
                <option value="heading">Titre</option>
                <option value="text">Texte</option>
                <option value="image">Image</option>
                <option value="button">Bouton</option>
                <option value="two-columns">Deux colonnes</option>
                <option value="spacer">Espace</option>
            </select>
            <button
                type="button"
                @click="addBlock()"
                class="rounded-full bg-[#111827] px-3 py-1 text-xxxs text-white hover:bg-black"
            >
                Ajouter
            </button>
        </div>
    </div>

    {{-- Zone d’édition des blocs --}}
    <div class="space-y-2">
        <template x-for="(block, index) in blocks" :key="block.id">
            <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-xxxs font-semibold text-gray-700">
                        <span x-text="label(block.type)"></span>
                    </div>
                    <div class="flex items-center gap-1 text-xxxs">
                        <button type="button" @click="moveUp(index)" class="px-1 py-0.5 hover:bg-gray-100 rounded">
                            ↑
                        </button>
                        <button type="button" @click="moveDown(index)" class="px-1 py-0.5 hover:bg-gray-100 rounded">
                            ↓
                        </button>
                        <button type="button" @click="removeBlock(index)" class="px-1 py-0.5 text-red-500 hover:bg-red-50 rounded">
                            ✕
                        </button>
                    </div>
                </div>

                {{-- Config par type --}}

                {{-- Heading --}}
                <template x-if="block.type === 'heading'">
                    <div class="space-y-1">
                        <select x-model="block.props.level"
                                @change="sync()"
                                class="rounded border border-gray-200 pr-2 pl-4 py-1 text-xxxs">
                            <option value="h1">H1</option>
                            <option value="h2">H2</option>
                            <option value="h3">H3</option>
                            <option value="h4">H4</option>
                        </select>
                        <input type="text"
                               x-model="block.props.text"
                               @input="sync()"
                               placeholder="Texte du titre"
                               class="w-full rounded border border-gray-200 px-2 py-1 text-xs">
                    </div>
                </template>

                {{-- Text --}}
                <template x-if="block.type === 'text'">
                    <div>
                        <textarea
                            x-model="block.props.html"
                            @input="sync()"
                            class="w-full rounded border border-gray-200 px-2 py-1 text-xs h-24"
                            placeholder="Contenu texte (HTML simple autorisé)"
                        ></textarea>
                    </div>
                </template>

                {{-- Image --}}
                <template x-if="block.type === 'image'">
                    <div class="space-y-1">
                        <input type="text"
                               x-model="block.props.url"
                               @input="sync()"
                               placeholder="URL de l'image"
                               class="w-full rounded border border-gray-200 px-2 py-1 text-xs">
                        <input type="text"
                               x-model="block.props.alt"
                               @input="sync()"
                               placeholder="Texte alternatif"
                               class="w-full rounded border border-gray-200 px-2 py-1 text-xs">
                    </div>
                </template>

                {{-- Button --}}
                <template x-if="block.type === 'button'">
                    <div class="space-y-1">
                        <input type="text"
                               x-model="block.props.label"
                               @input="sync()"
                               placeholder="Texte du bouton"
                               class="w-full rounded border border-gray-200 px-2 py-1 text-xs">
                        <input type="text"
                               x-model="block.props.url"
                               @input="sync()"
                               placeholder="Lien (URL ou slug)"
                               class="w-full rounded border border-gray-200 px-2 py-1 text-xs">
                    </div>
                </template>

                {{-- Two columns --}}
                <template x-if="block.type === 'two-columns'">
                    <div class="grid grid-cols-2 gap-2">
                        <textarea
                            x-model="block.props.left"
                            @input="sync()"
                            class="rounded border border-gray-200 px-2 py-1 text-xs h-20"
                            placeholder="Colonne gauche (texte HTML)"
                        ></textarea>
                        <textarea
                            x-model="block.props.right"
                            @input="sync()"
                            class="rounded border border-gray-200 px-2 py-1 text-xs h-20"
                            placeholder="Colonne droite (texte HTML ou image)"
                        ></textarea>
                    </div>
                </template>

                {{-- Spacer --}}
                <template x-if="block.type === 'spacer'">
                    <div class="space-y-1">
                        <label class="text-xxxs text-gray-600">Hauteur (px)</label>
                        <input type="number"
                               x-model.number="block.props.size"
                               @input="sync()"
                               class="w-24 rounded border border-gray-200 px-2 py-1 text-xs"
                               min="8" max="200">
                    </div>
                </template>
            </div>
        </template>

        <input type="hidden" name="{{ $field }}" x-ref="input">
    </div>

    {{-- Aperçu live --}}
    <div class="pt-3 border-t border-gray-200 space-y-2">
        <div class="text-xxxs font-medium text-gray-500 flex items-center gap-2">
            Aperçu en direct
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>

        <div class="rounded-2xl bg-[#f9fafb] border border-gray-100 px-4 py-4">
            <template x-if="!blocks.length">
                <div class="text-xxxs text-gray-400">
                    Ajoutez des blocs pour voir un aperçu du rendu de la page.
                </div>
            </template>

            <template x-for="block in blocks" :key="'preview-' + block.id">
                <div class="mb-4 last:mb-0">
                    {{-- Preview Heading --}}
                    <template x-if="block.type === 'heading'">
                        <div
                            :class="{
                                'text-3xl font-semibold text-neutral-900 leading-tight': block.props.level === 'h1',
                                'text-2xl font-semibold text-neutral-900 leading-snug': block.props.level === 'h2',
                                'text-xl font-semibold text-neutral-900': block.props.level === 'h3',
                                'text-lg font-semibold text-neutral-900': block.props.level === 'h4',
                            }"
                            x-text="block.props.text || 'Titre...'"
                        ></div>
                    </template>

                    {{-- Preview Text --}}
                    <template x-if="block.type === 'text'">
                        <div class="prose prose-xs text-neutral-700"
                             x-html="block.props.html || '<p>Texte de paragraphe…</p>'">
                        </div>
                    </template>

                    {{-- Preview Image --}}
                    <template x-if="block.type === 'image'">
                        <div>
                            <template x-if="block.props.url">
                                <img :src="block.props.url"
                                     :alt="block.props.alt || ''"
                                     class="w-full max-h-64 object-cover rounded-xl border border-gray-200">
                            </template>
                            <template x-if="!block.props.url">
                                <div class="w-full h-24 rounded-xl border border-dashed border-gray-300 flex items-center justify-center text-xxxs text-gray-400">
                                    Aperçu de l'image
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Preview Button --}}
                    <template x-if="block.type === 'button'">
                        <div>
                            <a
                                href="#"
                                class="inline-flex rounded-full bg-black px-4 py-1.5 text-xxxs text-white"
                                x-text="block.props.label || 'Bouton'"
                            ></a>
                        </div>
                    </template>

                    {{-- Preview Two columns --}}
                    <template x-if="block.type === 'two-columns'">
                        <div class="grid gap-3 md:grid-cols-2 text-xs text-neutral-700">
                            <div x-html="block.props.left || '<p>Colonne gauche</p>'"></div>
                            <div x-html="block.props.right || '<p>Colonne droite</p>'"></div>
                        </div>
                    </template>

                    {{-- Preview Spacer --}}
                    <template x-if="block.type === 'spacer'">
                        <div>
                            <div class="w-full bg-transparent"
                                 :style="`height: ${block.props.size || 32}px`"></div>
                            <div class="text-xxxs text-gray-400">
                                Espace de <span x-text="block.props.size || 32"></span> px
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
