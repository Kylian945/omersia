<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Omersia\Apparence\Rules\ValidatePageBuilderSchema;

final class EcommercePageUpdateRequest extends FormRequest
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
            'is_active' => ['boolean'],
            'content_json' => [
                'sometimes',
                'string',
                new ValidatePageBuilderSchema,
            ],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'noindex' => ['boolean'],
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

            'is_active.boolean' => 'Le statut actif doit être vrai ou faux.',

            'content_json.string' => 'Le contenu JSON doit être une chaîne de caractères.',

            'meta_title.string' => 'Le meta titre doit être une chaîne de caractères.',
            'meta_title.max' => 'Le meta titre ne peut pas dépasser 255 caractères.',

            'meta_description.string' => 'La meta description doit être une chaîne de caractères.',

            'noindex.boolean' => 'Le statut noindex doit être vrai ou faux.',
        ];
    }
}
