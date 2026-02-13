<?php

declare(strict_types=1);

namespace Omersia\Apparence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ThemeUploadRequest extends FormRequest
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
            'theme' => ['required', 'file', 'mimes:zip', 'max:51200'],
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
            'theme.required' => 'Le fichier du thème est obligatoire.',
            'theme.file' => 'Le thème doit être un fichier.',
            'theme.mimes' => 'Le thème doit être au format ZIP.',
            'theme.max' => 'Le thème ne peut pas dépasser 50 Mo.',
        ];
    }
}
