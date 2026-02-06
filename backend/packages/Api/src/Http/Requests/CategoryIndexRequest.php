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
