<?php

declare(strict_types=1);

namespace Omersia\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DiscountUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('discounts.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'method' => ['required', 'in:code,automatic'],
            'type' => ['required', 'in:product,order,shipping,buy_x_get_y'],

            'code' => ['nullable', 'string', 'max:64'],
            'value_type' => ['nullable', 'in:percentage,fixed_amount,free_shipping'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer'],

            'product_scope' => ['nullable', 'in:all,products,collections'],
            'product_ids' => ['array'],
            'product_ids.*' => ['integer', 'exists:products,id'],

            'collection_ids' => ['array'],
            'collection_ids.*' => ['integer', 'exists:categories,id'],

            'customer_selection' => ['required', 'in:all,groups,customers'],
            'customer_group_ids' => ['array'],
            'customer_group_ids.*' => ['integer', 'exists:customer_groups,id'],

            'customer_ids' => ['array'],
            'customer_ids.*' => ['integer', 'exists:customers,id'],

            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'min_quantity' => ['nullable', 'integer', 'min:0'],

            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],

            'buy_quantity' => ['nullable', 'integer', 'min:1'],
            'get_quantity' => ['nullable', 'integer', 'min:1'],
            'get_is_free' => ['nullable', 'boolean'],

            'combines_with_product_discounts' => ['nullable', 'boolean'],
            'combines_with_order_discounts' => ['nullable', 'boolean'],
            'combines_with_shipping_discounts' => ['nullable', 'boolean'],

            'is_active' => ['nullable', 'boolean'],
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
            'name.required' => 'Le nom de la réduction est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'method.required' => 'La méthode est obligatoire.',
            'method.in' => 'La méthode doit être "code" ou "automatic".',

            'type.required' => 'Le type de réduction est obligatoire.',
            'type.in' => 'Le type doit être "product", "order", "shipping" ou "buy_x_get_y".',

            'code.string' => 'Le code doit être une chaîne de caractères.',
            'code.max' => 'Le code ne peut pas dépasser 64 caractères.',

            'value_type.in' => 'Le type de valeur doit être "percentage", "fixed_amount" ou "free_shipping".',

            'value.numeric' => 'La valeur doit être un nombre.',
            'value.min' => 'La valeur ne peut pas être négative.',

            'priority.integer' => 'La priorité doit être un nombre entier.',

            'product_scope.in' => 'Le scope produit doit être "all", "products" ou "collections".',

            'product_ids.array' => 'Les IDs de produits doivent être un tableau.',
            'product_ids.*.integer' => 'Chaque ID de produit doit être un nombre entier.',
            'product_ids.*.exists' => 'Le produit sélectionné n\'existe pas.',

            'collection_ids.array' => 'Les IDs de collections doivent être un tableau.',
            'collection_ids.*.integer' => 'Chaque ID de collection doit être un nombre entier.',
            'collection_ids.*.exists' => 'La collection sélectionnée n\'existe pas.',

            'customer_selection.required' => 'La sélection client est obligatoire.',
            'customer_selection.in' => 'La sélection client doit être "all", "groups" ou "customers".',

            'customer_group_ids.array' => 'Les IDs de groupes doivent être un tableau.',
            'customer_group_ids.*.integer' => 'Chaque ID de groupe doit être un nombre entier.',
            'customer_group_ids.*.exists' => 'Le groupe de clients sélectionné n\'existe pas.',

            'customer_ids.array' => 'Les IDs de clients doivent être un tableau.',
            'customer_ids.*.integer' => 'Chaque ID de client doit être un nombre entier.',
            'customer_ids.*.exists' => 'Le client sélectionné n\'existe pas.',

            'min_subtotal.numeric' => 'Le montant minimum doit être un nombre.',
            'min_subtotal.min' => 'Le montant minimum ne peut pas être négatif.',

            'min_quantity.integer' => 'La quantité minimum doit être un nombre entier.',
            'min_quantity.min' => 'La quantité minimum ne peut pas être négative.',

            'starts_at.date' => 'La date de début doit être une date valide.',

            'ends_at.date' => 'La date de fin doit être une date valide.',
            'ends_at.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',

            'usage_limit.integer' => 'La limite d\'utilisation doit être un nombre entier.',
            'usage_limit.min' => 'La limite d\'utilisation doit être d\'au moins 1.',

            'usage_limit_per_customer.integer' => 'La limite par client doit être un nombre entier.',
            'usage_limit_per_customer.min' => 'La limite par client doit être d\'au moins 1.',

            'buy_quantity.integer' => 'La quantité à acheter doit être un nombre entier.',
            'buy_quantity.min' => 'La quantité à acheter doit être d\'au moins 1.',

            'get_quantity.integer' => 'La quantité offerte doit être un nombre entier.',
            'get_quantity.min' => 'La quantité offerte doit être d\'au moins 1.',

            'get_is_free.boolean' => 'Le champ gratuit doit être vrai ou faux.',

            'combines_with_product_discounts.boolean' => 'La combinaison avec les réductions produits doit être vrai ou faux.',
            'combines_with_order_discounts.boolean' => 'La combinaison avec les réductions commandes doit être vrai ou faux.',
            'combines_with_shipping_discounts.boolean' => 'La combinaison avec les réductions livraison doit être vrai ou faux.',

            'is_active.boolean' => 'Le statut actif doit être vrai ou faux.',
        ];
    }
}
