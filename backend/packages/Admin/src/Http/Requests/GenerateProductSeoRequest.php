<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GenerateProductSeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->canAny(['products.create', 'products.update']);
    }

    /**
     * @return array<string, array<int, \Illuminate\Validation\Rules\In|string>>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:3', 'max:2000'],
            'target_field' => [
                'required',
                'string',
                Rule::in(['name', 'short_description', 'description', 'meta_title', 'meta_description']),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'categories' => ['nullable', 'array', 'max:30'],
            'categories.*' => ['string', 'max:120'],
        ];
    }
}
