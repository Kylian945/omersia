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
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'preview_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
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

            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'description.string' => 'La description doit être une chaîne de caractères.',

            'preview_image.image' => 'Le fichier doit être une image.',
            'preview_image.mimes' => 'L\'image de prévisualisation doit être au format : jpeg, png, jpg ou webp.',
            'preview_image.max' => 'L\'image de prévisualisation ne peut pas dépasser 2 Mo.',
        ];
    }
}
