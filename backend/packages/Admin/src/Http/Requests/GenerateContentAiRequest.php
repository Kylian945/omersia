<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GenerateContentAiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->canAny([
            'categories.view',
            'categories.create',
            'categories.update',
            'pages.view',
            'pages.create',
            'pages.update',
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:3', 'max:2000'],
            'context' => ['required', 'string', Rule::in(['category', 'cms_page', 'ecommerce_page'])],
            'target_field' => [
                'required',
                'string',
                Rule::in(['name', 'description', 'title', 'meta_title', 'meta_description']),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'locale' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $context = (string) $this->input('context', '');
            $targetField = (string) $this->input('target_field', '');

            $allowedByContext = [
                'category' => ['name', 'description', 'meta_title', 'meta_description'],
                'cms_page' => ['title', 'meta_title', 'meta_description'],
                'ecommerce_page' => ['title', 'meta_title', 'meta_description'],
            ];

            if (! isset($allowedByContext[$context])) {
                return;
            }

            if (! in_array($targetField, $allowedByContext[$context], true)) {
                $validator->errors()->add(
                    'target_field',
                    'Le champ cible sélectionné n\'est pas valide pour ce contexte.'
                );
            }
        });
    }
}
