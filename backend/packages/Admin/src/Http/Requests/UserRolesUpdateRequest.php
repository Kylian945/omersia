<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UserRolesUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('users.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
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
            'roles.array' => 'Les rôles doivent être un tableau.',
            'roles.*.exists' => 'Le rôle sélectionné n\'existe pas.',
        ];
    }
}
