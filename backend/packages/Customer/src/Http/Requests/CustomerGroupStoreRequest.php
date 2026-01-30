<?php

declare(strict_types=1);

namespace Omersia\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CustomerGroupStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('customers.create');
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
            'code' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
            'customer_ids' => ['array'],
            'customer_ids.*' => ['integer', 'exists:customers,id'],
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
            'name.required' => 'Le nom du groupe est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'code.string' => 'Le code doit être une chaîne de caractères.',
            'code.max' => 'Le code ne peut pas dépasser 64 caractères.',

            'description.string' => 'La description doit être une chaîne de caractères.',

            'is_default.boolean' => 'Le statut par défaut doit être vrai ou faux.',

            'customer_ids.array' => 'Les IDs de clients doivent être un tableau.',
            'customer_ids.*.integer' => 'Chaque ID de client doit être un nombre entier.',
            'customer_ids.*.exists' => 'Le client sélectionné n\'existe pas.',
        ];
    }
}
