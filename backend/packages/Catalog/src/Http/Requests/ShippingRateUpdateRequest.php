<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ShippingRateUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('shipping.configure');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shipping_zone_id' => ['nullable', 'integer', 'exists:shipping_zones,id'],
            'min_weight' => ['nullable', 'numeric', 'min:0'],
            'max_weight' => ['nullable', 'numeric', 'min:0', 'gte:min_weight'],
            'price' => ['required', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:0'],
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
            'shipping_zone_id.integer' => 'La zone d\'expédition doit être un identifiant valide.',
            'shipping_zone_id.exists' => 'La zone d\'expédition sélectionnée n\'existe pas.',

            'min_weight.numeric' => 'Le poids minimum doit être un nombre.',
            'min_weight.min' => 'Le poids minimum ne peut pas être négatif.',

            'max_weight.numeric' => 'Le poids maximum doit être un nombre.',
            'max_weight.min' => 'Le poids maximum ne peut pas être négatif.',
            'max_weight.gte' => 'Le poids maximum doit être supérieur ou égal au poids minimum.',

            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix ne peut pas être négatif.',

            'priority.integer' => 'La priorité doit être un nombre entier.',
            'priority.min' => 'La priorité ne peut pas être négative.',
        ];
    }
}
