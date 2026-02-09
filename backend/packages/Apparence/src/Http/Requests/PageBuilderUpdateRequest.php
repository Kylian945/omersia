<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Omersia\Apparence\Rules\ValidatePageBuilderSchema;

final class PageBuilderUpdateRequest extends FormRequest
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
            'content_json' => [
                'required',
                'string',
                new ValidatePageBuilderSchema,
            ],
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
            'content_json.required' => 'Le contenu JSON est obligatoire.',
            'content_json.string' => 'Le contenu JSON doit être une chaîne de caractères.',
        ];
    }
}
