<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ShippingMethodStoreRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:255', 'unique:shipping_methods,code'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_time' => ['nullable', 'string', 'max:255'],
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
            'code.required' => 'Le code de la méthode d\'expédition est obligatoire.',
            'code.string' => 'Le code doit être une chaîne de caractères.',
            'code.max' => 'Le code ne peut pas dépasser 255 caractères.',
            'code.unique' => 'Ce code est déjà utilisé.',

            'name.required' => 'Le nom de la méthode d\'expédition est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix ne peut pas être négatif.',

            'delivery_time.string' => 'Le délai de livraison doit être une chaîne de caractères.',
            'delivery_time.max' => 'Le délai de livraison ne peut pas dépasser 255 caractères.',

            'is_active.boolean' => 'Le statut actif doit être vrai ou faux.',
        ];
    }
}
