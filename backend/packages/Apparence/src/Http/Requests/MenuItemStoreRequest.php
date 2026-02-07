<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MenuItemStoreRequest extends FormRequest
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
            'type' => ['required', 'in:category,cms_page,link,text'],
            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                Rule::requiredIf(fn (): bool => $this->input('type') === 'category'),
            ],
            'cms_page_id' => [
                'nullable',
                'integer',
                'exists:cms_pages,id',
                Rule::requiredIf(fn (): bool => $this->input('type') === 'cms_page'),
            ],
            'url' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn (): bool => $this->input('type') === 'link'),
            ],
            'position' => ['nullable', 'integer', 'min:1'],
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
            'label.required' => 'Le libellé est obligatoire.',
            'label.string' => 'Le libellé doit être une chaîne de caractères.',
            'label.max' => 'Le libellé ne peut pas dépasser 255 caractères.',

            'type.required' => 'Le type est obligatoire.',
            'type.in' => 'Le type doit être : category, cms_page, link ou text.',

            'category_id.integer' => 'L\'ID de la catégorie doit être un nombre entier.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'category_id.required' => 'La catégorie est obligatoire pour ce type de lien.',

            'cms_page_id.integer' => 'L\'ID de la page CMS doit être un nombre entier.',
            'cms_page_id.exists' => 'La page CMS sélectionnée n\'existe pas.',
            'cms_page_id.required' => 'La page CMS est obligatoire pour ce type de lien.',

            'url.string' => 'L\'URL doit être une chaîne de caractères.',
            'url.max' => 'L\'URL ne peut pas dépasser 255 caractères.',
            'url.required' => 'L\'URL est obligatoire pour un lien spécifique.',

            'position.integer' => 'La position doit être un nombre entier.',
            'position.min' => 'La position doit être au minimum 1.',

            'is_active.boolean' => 'Le statut actif doit être vrai ou faux.',
        ];
    }
}
