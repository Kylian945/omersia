<?php

declare(strict_types=1);

namespace Omersia\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateStripeConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('settings.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'mode' => ['required', 'in:test,live'],
            'currency' => ['nullable', 'string', 'max:10'],
            'publishable_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
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
            'enabled.boolean' => 'Le statut actif doit être vrai ou faux.',

            'mode.required' => 'Le mode est obligatoire.',
            'mode.in' => 'Le mode doit être "test" ou "live".',

            'currency.string' => 'La devise doit être une chaîne de caractères.',
            'currency.max' => 'La devise ne peut pas dépasser 10 caractères.',

            'publishable_key.string' => 'La clé publique doit être une chaîne de caractères.',
            'publishable_key.max' => 'La clé publique ne peut pas dépasser 255 caractères.',

            'secret_key.string' => 'La clé secrète doit être une chaîne de caractères.',
            'secret_key.max' => 'La clé secrète ne peut pas dépasser 255 caractères.',

            'webhook_secret.string' => 'Le secret webhook doit être une chaîne de caractères.',
            'webhook_secret.max' => 'Le secret webhook ne peut pas dépasser 255 caractères.',
        ];
    }
}
