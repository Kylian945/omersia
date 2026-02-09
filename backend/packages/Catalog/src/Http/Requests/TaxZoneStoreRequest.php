<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TaxZoneStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('settings.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:tax_zones,code'],
            'description' => ['nullable', 'string'],
            'countries' => ['nullable', 'array'],
            'countries_input' => ['nullable', 'string'],
            'states' => ['nullable', 'array'],
            'postal_codes' => ['nullable', 'array'],
            'postal_codes_input' => ['nullable', 'string'],
            'priority' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la zone fiscale est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'code.required' => 'Le code de la zone fiscale est obligatoire.',
            'code.string' => 'Le code doit être une chaîne de caractères.',
            'code.max' => 'Le code ne peut pas dépasser 50 caractères.',
            'code.unique' => 'Ce code est déjà utilisé.',

            'description.string' => 'La description doit être une chaîne de caractères.',

            'countries.array' => 'Les pays doivent être un tableau.',
            'countries_input.string' => 'Les pays doivent être une chaîne de caractères.',

            'states.array' => 'Les états/régions doivent être un tableau.',

            'postal_codes.array' => 'Les codes postaux doivent être un tableau.',
            'postal_codes_input.string' => 'Les codes postaux doivent être une chaîne de caractères.',

            'priority.required' => 'La priorité est obligatoire.',
            'priority.integer' => 'La priorité doit être un nombre entier.',
            'priority.min' => 'La priorité ne peut pas être négative.',

            'is_active.boolean' => 'Le statut actif doit être vrai ou faux.',
        ];
    }
}
