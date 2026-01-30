<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'type' => ['required', 'in:category,link,text'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'url' => ['nullable', 'string', 'max:255'],
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
            'type.in' => 'Le type doit être : category, link ou text.',

            'category_id.integer' => 'L\'ID de la catégorie doit être un nombre entier.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',

            'url.string' => 'L\'URL doit être une chaîne de caractères.',
            'url.max' => 'L\'URL ne peut pas dépasser 255 caractères.',

            'position.integer' => 'La position doit être un nombre entier.',
            'position.min' => 'La position doit être au minimum 1.',

            'is_active.boolean' => 'Le statut actif doit être vrai ou faux.',
        ];
    }
}
