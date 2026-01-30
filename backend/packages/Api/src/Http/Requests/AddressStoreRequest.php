<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddressStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_default_billing' => ['sometimes', 'boolean'],
            'is_default_shipping' => ['sometimes', 'boolean'],
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
            'label.required' => 'Le libellé de l\'adresse est obligatoire.',
            'label.string' => 'Le libellé doit être une chaîne de caractères.',
            'label.max' => 'Le libellé ne peut pas dépasser 255 caractères.',

            'first_name.string' => 'Le prénom doit être une chaîne de caractères.',
            'first_name.max' => 'Le prénom ne peut pas dépasser 255 caractères.',

            'last_name.string' => 'Le nom doit être une chaîne de caractères.',
            'last_name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'company.string' => 'Le nom de société doit être une chaîne de caractères.',
            'company.max' => 'Le nom de société ne peut pas dépasser 255 caractères.',

            'line1.required' => 'La première ligne d\'adresse est obligatoire.',
            'line1.string' => 'La première ligne d\'adresse doit être une chaîne de caractères.',
            'line1.max' => 'La première ligne d\'adresse ne peut pas dépasser 255 caractères.',

            'line2.string' => 'La deuxième ligne d\'adresse doit être une chaîne de caractères.',
            'line2.max' => 'La deuxième ligne d\'adresse ne peut pas dépasser 255 caractères.',

            'postcode.required' => 'Le code postal est obligatoire.',
            'postcode.string' => 'Le code postal doit être une chaîne de caractères.',
            'postcode.max' => 'Le code postal ne peut pas dépasser 20 caractères.',

            'city.required' => 'La ville est obligatoire.',
            'city.string' => 'La ville doit être une chaîne de caractères.',
            'city.max' => 'La ville ne peut pas dépasser 255 caractères.',

            'state.string' => 'L\'état/région doit être une chaîne de caractères.',
            'state.max' => 'L\'état/région ne peut pas dépasser 255 caractères.',

            'country.string' => 'Le pays doit être une chaîne de caractères.',
            'country.max' => 'Le pays ne peut pas dépasser 255 caractères.',

            'phone.string' => 'Le téléphone doit être une chaîne de caractères.',
            'phone.max' => 'Le téléphone ne peut pas dépasser 30 caractères.',

            'is_default_billing.boolean' => 'L\'adresse de facturation par défaut doit être vrai ou faux.',
            'is_default_shipping.boolean' => 'L\'adresse de livraison par défaut doit être vrai ou faux.',
        ];
    }
}
