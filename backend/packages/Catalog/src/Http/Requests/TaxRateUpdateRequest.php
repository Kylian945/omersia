<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TaxRateUpdateRequest extends FormRequest
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
            'type' => ['required', 'in:percentage,fixed'],
            'rate' => ['required', 'numeric', 'min:0'],
            'compound' => ['nullable', 'boolean'],
            'shipping_taxable' => ['nullable', 'boolean'],
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
            'name.required' => 'Le nom du taux de taxe est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'type.required' => 'Le type de taxe est obligatoire.',
            'type.in' => 'Le type de taxe doit être "percentage" (pourcentage) ou "fixed" (fixe).',

            'rate.required' => 'Le taux de taxe est obligatoire.',
            'rate.numeric' => 'Le taux de taxe doit être un nombre.',
            'rate.min' => 'Le taux de taxe ne peut pas être négatif.',

            'compound.boolean' => 'La taxe composée doit être vrai ou faux.',

            'shipping_taxable.boolean' => 'L\'expédition taxable doit être vrai ou faux.',

            'priority.required' => 'La priorité est obligatoire.',
            'priority.integer' => 'La priorité doit être un nombre entier.',
            'priority.min' => 'La priorité ne peut pas être négative.',

            'is_active.boolean' => 'Le statut actif doit être vrai ou faux.',
        ];
    }
}
