<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PageStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('pages.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_home' => ['nullable', 'boolean'],
            'content_json' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'noindex' => ['nullable', 'boolean'],
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
            'title.required' => 'Le titre de la page est obligatoire.',
            'title.string' => 'Le titre doit être une chaîne de caractères.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',

            'slug.required' => 'Le slug est obligatoire.',
            'slug.string' => 'Le slug doit être une chaîne de caractères.',
            'slug.max' => 'Le slug ne peut pas dépasser 255 caractères.',

            'type.string' => 'Le type doit être une chaîne de caractères.',

            'is_active.boolean' => 'Le statut actif doit être vrai ou faux.',
            'is_home.boolean' => 'Le statut page d\'accueil doit être vrai ou faux.',

            'content_json.string' => 'Le contenu JSON doit être une chaîne de caractères.',

            'meta_title.string' => 'Le méta titre doit être une chaîne de caractères.',
            'meta_title.max' => 'Le méta titre ne peut pas dépasser 255 caractères.',

            'meta_description.string' => 'La méta description doit être une chaîne de caractères.',

            'noindex.boolean' => 'Le statut noindex doit être vrai ou faux.',
        ];
    }
}
