<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AskBackofficeAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:3', 'max:3000'],
            'history' => ['nullable', 'array', 'max:20'],
            'history.*.role' => ['required_with:history', 'string', Rule::in(['user', 'assistant'])],
            'history.*.content' => ['required_with:history', 'string', 'min:1', 'max:1000'],
        ];
    }
}
