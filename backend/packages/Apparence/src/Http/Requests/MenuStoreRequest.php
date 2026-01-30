<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MenuStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:menus,slug'],
            'location' => ['required', 'string', 'max:50'],
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
            'name.required' => 'Le nom du menu est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'slug.required' => 'Le slug est obligatoire.',
            'slug.string' => 'Le slug doit être une chaîne de caractères.',
            'slug.max' => 'Le slug ne peut pas dépasser 255 caractères.',
            'slug.alpha_dash' => 'Le slug ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'slug.unique' => 'Ce slug est déjà utilisé.',

            'location.required' => 'L\'emplacement est obligatoire.',
            'location.string' => 'L\'emplacement doit être une chaîne de caractères.',
            'location.max' => 'L\'emplacement ne peut pas dépasser 50 caractères.',
        ];
    }
}
