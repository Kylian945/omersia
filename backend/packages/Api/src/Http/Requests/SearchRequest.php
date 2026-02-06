<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SearchRequest extends FormRequest
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
            'q' => ['nullable', 'string'],
            'locale' => ['sometimes', 'string', 'in:fr,en'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'categories' => ['sometimes', 'string'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock_only' => ['sometimes', 'boolean'],
        ];
    }
}
