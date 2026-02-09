<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CategoryUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('categories.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = is_object($category) && isset($category->id)
            ? (int) $category->id
            : (is_numeric($category) ? (int) $category : null);

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('category_translations', 'slug')->where(function ($query) use ($categoryId) {
                    return $query->where('category_id', '!=', $categoryId);
                }),
            ],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn([$categoryId])],
            'is_active' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'delete_image' => ['nullable', 'boolean'],
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
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'slug.required' => 'Le slug est obligatoire.',
            'slug.string' => 'Le slug doit être une chaîne de caractères.',
            'slug.max' => 'Le slug ne peut pas dépasser 255 caractères.',
            'slug.unique' => 'Ce slug est déjà utilisé.',

            'description.string' => 'La description doit être une chaîne de caractères.',

            'meta_title.string' => 'Le titre meta doit être une chaîne de caractères.',
            'meta_title.max' => 'Le titre meta ne peut pas dépasser 255 caractères.',

            'meta_description.string' => 'La description meta doit être une chaîne de caractères.',
            'meta_description.max' => 'La description meta ne peut pas dépasser 500 caractères.',

            'parent_id.integer' => 'La catégorie parente doit être un identifiant valide.',
            'parent_id.exists' => 'La catégorie parente sélectionnée n\'existe pas.',
            'parent_id.not_in' => 'Une catégorie ne peut pas être sa propre parente.',

            'is_active.boolean' => 'Le statut actif doit être vrai ou faux.',

            'position.integer' => 'La position doit être un nombre entier.',
            'position.min' => 'La position ne peut pas être négative.',

            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être au format jpeg, png, jpg, gif ou webp.',
            'image.max' => 'L\'image ne peut pas dépasser 2 Mo.',
        ];
    }
}
