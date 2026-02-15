<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->canAny(['products.create', 'products.update']);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:3', 'max:1500'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'source_image_ids' => ['nullable', 'array', 'max:1'],
            'source_image_ids.*' => ['integer', 'distinct'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $sourceImageIds = $this->input('source_image_ids', []);
            $sourceCount = is_array($sourceImageIds)
                ? count(array_filter($sourceImageIds, static fn ($id): bool => is_numeric($id)))
                : 0;
            $productId = $this->input('product_id');

            if ($sourceCount > 0 && (! is_numeric($productId) || (int) $productId <= 0)) {
                $validator->errors()->add(
                    'product_id',
                    'Le produit est requis pour utiliser les images existantes comme source.'
                );
            }
        });
    }
}
