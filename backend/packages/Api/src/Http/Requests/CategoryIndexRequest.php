<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CategoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('parent_only')) {
            $this->merge(['parent_only' => filter_var($this->input('parent_only'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'string', 'in:fr,en'],
            'parent_only' => ['sometimes', 'boolean'],
        ];
    }
}
