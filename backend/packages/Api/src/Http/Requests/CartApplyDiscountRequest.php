<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CartApplyDiscountRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'integer'],
            'existing_types' => ['sometimes', 'array'],
            'existing_types.*' => ['string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.variant_id' => ['nullable', 'integer'],
        ];
    }
}
