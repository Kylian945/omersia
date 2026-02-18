<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('products.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = is_object($product) && isset($product->id)
            ? (int) $product->id
            : (is_numeric($product) ? (int) $product : null);
        $defaultType = is_object($product) && isset($product->type)
            ? (string) $product->type
            : 'simple';
        $type = (string) $this->input('type', $defaultType);

        $rules = [
            'type' => ['required', 'in:simple,variant'],
            'is_active' => ['nullable', 'boolean'],
            'manage_stock' => ['nullable', 'boolean'],

            // Traductions
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],

            // Relations
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'related_products' => ['nullable', 'array'],
            'related_products.*' => ['integer', 'exists:products,id'],

            // Images
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'main_image' => ['nullable', 'string'],
            'ai_generated_images' => ['nullable', 'array', 'max:4'],
            'ai_generated_images.*' => ['string', 'max:8000000', 'regex:/^data:image\/(?:png|jpeg|jpg|webp);base64,[A-Za-z0-9+\/=\r\n]+$/'],
        ];

        if ($type === 'simple') {
            $rules['sku'] = ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)];
            $rules['stock_qty'] = ['nullable', 'integer', 'min:0'];
            $rules['price'] = ['required', 'numeric', 'min:0'];
            $rules['compare_at_price'] = ['nullable', 'numeric', 'min:0', 'gte:price'];
        } else {
            $rules['sku'] = ['nullable', 'string', 'max:255'];

            // Options pour les variantes
            $rules['options'] = ['required', 'array', 'min:1'];
            $rules['options.*.name'] = ['required', 'string', 'max:255'];
            $rules['options.*.values'] = ['required', 'array', 'min:1'];
            $rules['options.*.values.*'] = ['required', 'string', 'max:255'];

            // Variantes
            $rules['variants'] = ['required', 'array', 'min:1'];
            $rules['variants.*.id'] = ['nullable', 'integer'];
            $rules['variants.*.sku'] = ['required', 'string', 'max:255', 'distinct'];
            $rules['variants.*.label'] = ['required', 'string', 'max:255'];
            $rules['variants.*.is_active'] = ['nullable', 'boolean'];
            $rules['variants.*.stock_qty'] = ['nullable', 'integer', 'min:0'];
            $rules['variants.*.price'] = ['required', 'numeric', 'min:0'];
            $rules['variants.*.compare_at_price'] = ['nullable', 'numeric', 'min:0'];
            $rules['variants.*.image_key'] = ['nullable', 'string', 'max:50'];
            $rules['variants.*.values'] = ['required', 'array', 'min:1'];
        }

        return $rules;
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de produit est obligatoire.',
            'type.in' => 'Le type de produit doit être "simple" ou "variant".',

            'sku.required' => 'Le SKU est obligatoire.',
            'sku.string' => 'Le SKU doit être une chaîne de caractères.',
            'sku.max' => 'Le SKU ne peut pas dépasser 255 caractères.',
            'sku.unique' => 'Ce SKU est déjà utilisé.',

            'name.required' => 'Le nom du produit est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

            'slug.required' => 'Le slug est obligatoire.',
            'slug.string' => 'Le slug doit être une chaîne de caractères.',
            'slug.max' => 'Le slug ne peut pas dépasser 255 caractères.',

            'short_description.string' => 'La description courte doit être une chaîne de caractères.',
            'short_description.max' => 'La description courte ne peut pas dépasser 500 caractères.',

            'description.string' => 'La description doit être une chaîne de caractères.',

            'meta_title.string' => 'Le titre meta doit être une chaîne de caractères.',
            'meta_title.max' => 'Le titre meta ne peut pas dépasser 255 caractères.',

            'meta_description.string' => 'La description meta doit être une chaîne de caractères.',
            'meta_description.max' => 'La description meta ne peut pas dépasser 500 caractères.',

            'stock_qty.integer' => 'La quantité en stock doit être un nombre entier.',
            'stock_qty.min' => 'La quantité en stock ne peut pas être négative.',

            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix ne peut pas être négatif.',

            'compare_at_price.numeric' => 'Le prix comparatif doit être un nombre.',
            'compare_at_price.min' => 'Le prix comparatif ne peut pas être négatif.',
            'compare_at_price.gte' => 'Le prix comparatif doit être supérieur ou égal au prix.',

            'categories.array' => 'Les catégories doivent être un tableau.',
            'categories.*.integer' => 'Chaque catégorie doit être un identifiant valide.',
            'categories.*.exists' => 'La catégorie sélectionnée n\'existe pas.',

            'related_products.array' => 'Les produits associés doivent être un tableau.',
            'related_products.*.integer' => 'Chaque produit associé doit être un identifiant valide.',
            'related_products.*.exists' => 'Le produit associé sélectionné n\'existe pas.',

            'images.array' => 'Les images doivent être un tableau.',
            'images.*.image' => 'Chaque fichier doit être une image.',
            'images.*.mimes' => 'Les images doivent être au format JPEG, JPG, PNG ou WEBP.',
            'images.*.max' => 'Chaque image ne peut pas dépasser 5 Mo.',
            'ai_generated_images.array' => 'Les images IA doivent être un tableau.',
            'ai_generated_images.max' => 'Vous pouvez ajouter au maximum 4 images IA.',
            'ai_generated_images.*.regex' => 'Le format des images IA est invalide.',
            'ai_generated_images.*.max' => 'Une image IA est trop volumineuse.',

            // Messages pour les variantes
            'options.required' => 'Au moins une option est requise pour un produit à variantes.',
            'options.array' => 'Les options doivent être un tableau.',
            'options.min' => 'Au moins une option est requise.',
            'options.*.name.required' => 'Le nom de l\'option est obligatoire.',
            'options.*.name.string' => 'Le nom de l\'option doit être une chaîne de caractères.',
            'options.*.name.max' => 'Le nom de l\'option ne peut pas dépasser 255 caractères.',
            'options.*.values.required' => 'Les valeurs de l\'option sont obligatoires.',
            'options.*.values.array' => 'Les valeurs de l\'option doivent être un tableau.',
            'options.*.values.min' => 'Au moins une valeur est requise pour l\'option.',
            'options.*.values.*.required' => 'La valeur de l\'option est obligatoire.',
            'options.*.values.*.string' => 'La valeur de l\'option doit être une chaîne de caractères.',
            'options.*.values.*.max' => 'La valeur de l\'option ne peut pas dépasser 255 caractères.',

            'variants.required' => 'Au moins une variante est requise.',
            'variants.array' => 'Les variantes doivent être un tableau.',
            'variants.min' => 'Au moins une variante est requise.',
            'variants.*.id.integer' => 'L\'identifiant de variante est invalide.',
            'variants.*.sku.required' => 'Le SKU de la variante est obligatoire.',
            'variants.*.sku.string' => 'Le SKU de la variante doit être une chaîne de caractères.',
            'variants.*.sku.max' => 'Le SKU de la variante ne peut pas dépasser 255 caractères.',
            'variants.*.sku.distinct' => 'Chaque variante doit avoir un SKU unique.',
            'variants.*.label.required' => 'Le nom de la variante est obligatoire.',
            'variants.*.label.string' => 'Le nom de la variante doit être une chaîne de caractères.',
            'variants.*.label.max' => 'Le nom de la variante ne peut pas dépasser 255 caractères.',
            'variants.*.stock_qty.integer' => 'La quantité en stock de la variante doit être un nombre entier.',
            'variants.*.stock_qty.min' => 'La quantité en stock de la variante ne peut pas être négative.',
            'variants.*.price.required' => 'Le prix de la variante est obligatoire.',
            'variants.*.price.numeric' => 'Le prix de la variante doit être un nombre.',
            'variants.*.price.min' => 'Le prix de la variante ne peut pas être négatif.',
            'variants.*.compare_at_price.numeric' => 'Le prix comparatif de la variante doit être un nombre.',
            'variants.*.compare_at_price.min' => 'Le prix comparatif de la variante ne peut pas être négatif.',
            'variants.*.image_key.string' => 'La clé d\'image de la variante est invalide.',
            'variants.*.image_key.max' => 'La clé d\'image de la variante est trop longue.',
            'variants.*.values.required' => 'Les valeurs de la variante sont obligatoires.',
            'variants.*.values.array' => 'Les valeurs de la variante doivent être un tableau.',
            'variants.*.values.min' => 'Au moins une valeur est requise pour la variante.',
        ];
    }
}
