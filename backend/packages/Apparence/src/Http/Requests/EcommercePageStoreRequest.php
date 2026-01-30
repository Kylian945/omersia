<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EcommercePageStoreRequest extends FormRequest
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
            'type' => ['required', 'in:home,category,product'],
            'slug' => ['nullable', 'string'],
            'title' => ['required', 'string'],
            'locale' => ['required', 'string'],
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
            'type.required' => 'Le type de page est obligatoire.',
            'type.in' => 'Le type de page doit être : home, category ou product.',

            'slug.string' => 'Le slug doit être une chaîne de caractères.',

            'title.required' => 'Le titre est obligatoire.',
            'title.string' => 'Le titre doit être une chaîne de caractères.',

            'locale.required' => 'La langue est obligatoire.',
            'locale.string' => 'La langue doit être une chaîne de caractères.',
        ];
    }
}
