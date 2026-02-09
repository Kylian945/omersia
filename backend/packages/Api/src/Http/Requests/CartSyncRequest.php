<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CartSyncRequest extends FormRequest
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
            'token' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'currency' => ['nullable', 'string', 'size:3'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'integer'],
            'items.*.name' => ['required_with:items', 'string'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.oldPrice' => ['nullable', 'numeric', 'min:0'],
            'items.*.qty' => ['required_with:items', 'integer', 'min:1'],
            'items.*.imageUrl' => ['nullable', 'string'],
            'items.*.variantId' => ['nullable', 'integer'],
            'items.*.variantLabel' => ['nullable', 'string'],
        ];
    }
}
