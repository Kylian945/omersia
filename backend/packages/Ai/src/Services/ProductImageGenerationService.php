<?php

declare(strict_types=1);

namespace Omersia\Ai\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Omersia\Ai\Exceptions\AiGenerationException;
use Omersia\Ai\Models\AiProvider;
use Omersia\Catalog\Models\Product;
use Throwable;

class ProductImageGenerationService
{
    private const OPENAI_BASE_URL = 'https://api.openai.com/v1';

    private const LEGACY_IMAGE_MODEL = 'dall-e-2';

    private const ALT_IMAGE_MODEL = 'dall-e-3';

    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    private const MAX_EDIT_IMAGE_BYTES = 4 * 1024 * 1024;

    private const EDIT_IMAGE_SIZE = 1024;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function generate(array $input): array
    {
        $providers = AiProvider::query()
            ->where('is_enabled', true)
            ->get()
            ->filter(static fn (AiProvider $provider): bool => $provider->hasApiKey())
            ->values();

        if ($providers->isEmpty()) {
            throw new AiGenerationException(
                'Aucun provider IA actif avec clé API. Configure au moins un provider dans les paramètres IA.'
            );
        }

        $providers = $this->orderByDefaultProvider($providers);
        $sourceImage = $this->resolveSourceImage($input);
        $prompt = $this->buildPrompt($input, $sourceImage !== null);

        $lastError = null;
        $attempted = 0;

        foreach ($providers as $provider) {
            if (! $this->supportsImageGeneration($provider)) {
                continue;
            }

            $attempted++;

            try {
                $result = $this->generateWithProvider($provider, $prompt, $sourceImage);

                return [
                    'image_data_url' => $result['data_url'],
                    'mime_type' => $result['mime_type'],
                    'provider' => $provider->code,
                ];
            } catch (Throwable $e) {
                $lastError = $e;

                Log::warning('AI product image generation failed for provider.', [
                    'provider' => $provider->code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($attempted === 0) {
            throw new AiGenerationException(
                'Aucun provider compatible image trouvé. Utilise un provider avec driver OpenAI pour la génération d’images.'
            );
        }

        $fallbackMessage = 'La génération d’image IA a échoué sur tous les providers actifs. Vérifie les clés API, le modèle et le quota.';

        if ($lastError instanceof AiGenerationException) {
            $lastMessage = trim($lastError->getMessage());
            if ($lastMessage !== '') {
                $fallbackMessage = $lastMessage;
            }
        }

        throw new AiGenerationException($fallbackMessage, previous: $lastError);
    }

    /**
     * @param  Collection<int, AiProvider>  $providers
     * @return Collection<int, AiProvider>
     */
    private function orderByDefaultProvider(Collection $providers): Collection
    {
        $default = $providers->first(static fn (AiProvider $provider): bool => $provider->is_default);

        if (! $default instanceof AiProvider) {
            return $providers;
        }

        return collect([$default])->merge(
            $providers->reject(static fn (AiProvider $provider): bool => $provider->id === $default->id)->values()
        );
    }

    private function supportsImageGeneration(AiProvider $provider): bool
    {
        return $provider->getDriver() === 'openai';
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     binary: string,
     *     mime_type: string,
     *     filename: string,
     *     mask_binary: string,
     *     mask_filename: string
     * }|null
     */
    private function resolveSourceImage(array $input): ?array
    {
        $sourceIdsRaw = $input['source_image_ids'] ?? [];
        if (! is_array($sourceIdsRaw)) {
            return null;
        }

        $sourceIds = collect($sourceIdsRaw)
            ->filter(static fn ($value): bool => is_numeric($value))
            ->map(static fn ($value): int => (int) $value)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($sourceIds->isEmpty()) {
            return null;
        }

        $productId = $input['product_id'] ?? null;
        if (! is_numeric($productId) || (int) $productId <= 0) {
            throw new AiGenerationException('Produit source invalide pour la génération d’image.');
        }

        $product = Product::query()
            ->with(['images' => function ($query) use ($sourceIds): void {
                $query->whereIn('id', $sourceIds->all());
            }])
            ->find((int) $productId);

        if (! $product instanceof Product) {
            throw new AiGenerationException('Produit source introuvable.');
        }

        $firstSourceId = $sourceIds->first();
        $sourceImage = $product->images->firstWhere('is_main', true);

        if ($sourceImage === null && is_int($firstSourceId)) {
            $sourceImage = $product->images->firstWhere('id', $firstSourceId);
        }

        if ($sourceImage === null) {
            $sourceImage = $product->images->first();
        }

        if ($sourceImage === null) {
            throw new AiGenerationException('Image source invalide pour ce produit.');
        }

        $path = (string) $sourceImage->path;
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            throw new AiGenerationException(
                'Les images source externes ne sont pas supportées pour la génération IA.'
            );
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            throw new AiGenerationException('Le fichier image source est introuvable.');
        }

        $binary = $disk->get($path);
        $preparedImage = $this->prepareSourceImageForEdit($binary);

        return [
            'binary' => $preparedImage['binary'],
            'mime_type' => 'image/png',
            'filename' => 'product-source-'.$sourceImage->id.'.png',
            'mask_binary' => $preparedImage['mask_binary'],
            'mask_filename' => 'product-source-mask-'.$sourceImage->id.'.png',
        ];
    }

    /**
     * @return array{binary: string, mask_binary: string}
     */
    private function prepareSourceImageForEdit(string $binary): array
    {
        if (
            ! function_exists('imagecreatefromstring') ||
            ! function_exists('imagecreatetruecolor') ||
            ! function_exists('imagecopyresampled') ||
            ! function_exists('imagepng')
        ) {
            throw new AiGenerationException(
                'La génération d’image IA nécessite l’extension GD (imagecreatefromstring/imagepng).'
            );
        }

        $resource = @imagecreatefromstring($binary);
        if ($resource === false) {
            throw new AiGenerationException('Le fichier image source est invalide ou non décodable.');
        }

        $sourceWidth = imagesx($resource);
        $sourceHeight = imagesy($resource);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($resource);
            throw new AiGenerationException('Dimensions de l’image source invalides.');
        }

        $sourceSize = min($sourceWidth, $sourceHeight);
        $sourceX = (int) floor(($sourceWidth - $sourceSize) / 2);
        $sourceY = (int) floor(($sourceHeight - $sourceSize) / 2);

        $target = imagecreatetruecolor(self::EDIT_IMAGE_SIZE, self::EDIT_IMAGE_SIZE);
        if ($target === false) {
            imagedestroy($resource);
            throw new AiGenerationException('Impossible de préparer l’image source pour la génération IA.');
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, self::EDIT_IMAGE_SIZE, self::EDIT_IMAGE_SIZE, $transparent);

        $copied = imagecopyresampled(
            $target,
            $resource,
            0,
            0,
            $sourceX,
            $sourceY,
            self::EDIT_IMAGE_SIZE,
            self::EDIT_IMAGE_SIZE,
            $sourceSize,
            $sourceSize
        );

        imagedestroy($resource);

        if ($copied === false) {
            imagedestroy($target);
            throw new AiGenerationException('Impossible de redimensionner l’image source.');
        }

        // Garantit un canal alpha présent pour les endpoints d'édition qui exigent RGBA/LA/L.
        $transparentPixel = imagecolorallocatealpha($target, 0, 0, 0, 127);
        if ($transparentPixel !== false) {
            imagesetpixel($target, self::EDIT_IMAGE_SIZE - 1, self::EDIT_IMAGE_SIZE - 1, $transparentPixel);
        }

        $sourcePng = $this->imageResourceToPngBinary($target);
        imagedestroy($target);

        $mask = imagecreatetruecolor(self::EDIT_IMAGE_SIZE, self::EDIT_IMAGE_SIZE);
        if ($mask === false) {
            throw new AiGenerationException('Impossible de créer le mask pour la génération IA.');
        }

        imagealphablending($mask, false);
        imagesavealpha($mask, true);
        $maskTransparent = imagecolorallocatealpha($mask, 0, 0, 0, 127);
        imagefilledrectangle($mask, 0, 0, self::EDIT_IMAGE_SIZE, self::EDIT_IMAGE_SIZE, $maskTransparent);
        $maskPng = $this->imageResourceToPngBinary($mask);
        imagedestroy($mask);

        if (strlen($sourcePng) > self::MAX_EDIT_IMAGE_BYTES || strlen($maskPng) > self::MAX_EDIT_IMAGE_BYTES) {
            throw new AiGenerationException('Image source trop volumineuse pour l’édition IA (max 4MB).');
        }

        return [
            'binary' => $sourcePng,
            'mask_binary' => $maskPng,
        ];
    }

    private function imageResourceToPngBinary($resource): string
    {
        ob_start();
        $written = imagepng($resource, null, 6);
        $binary = ob_get_clean();

        if ($written !== true || ! is_string($binary) || $binary === '') {
            throw new AiGenerationException('Impossible de convertir l’image source en PNG.');
        }

        return $binary;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function buildPrompt(array $input, bool $hasSourceImage): string
    {
        $userPrompt = $this->normalizePromptSegment((string) ($input['prompt'] ?? ''), 700);

        $baseGoal = $hasSourceImage
            ? 'Créer une nouvelle image produit dérivée de l’image source.'
            : 'Créer une image produit e-commerce originale.';

        $promptGoal = $userPrompt !== '' ? $userPrompt : $baseGoal;
        $referenceConstraint = $hasSourceImage
            ? '- Utilise l’image de référence comme base principale (même produit, même identité visuelle globale).
- Ne remplace pas le produit par un autre type d’objet, sauf demande explicite dans le prompt.'
            : '- Crée une image cohérente avec le prompt.';

        $prompt = <<<PROMPT
{$promptGoal}

Contraintes impératives:
- Image adaptée à une fiche produit e-commerce.
- Composition claire, sujet principal net, rendu qualitatif.
- Pas de texte incrusté, pas de watermark, pas de logo ajouté automatiquement.
- Fond propre et sobre.
{$referenceConstraint}
PROMPT;

        return $this->normalizePromptSegment($prompt, 950);
    }

    private function normalizePromptSegment(string $value, int $maxLength): string
    {
        $normalized = strip_tags($value);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? '';
        $normalized = trim($normalized);

        if ($normalized === '' || $maxLength <= 0) {
            return '';
        }

        if (mb_strlen($normalized) <= $maxLength) {
            return $normalized;
        }

        return rtrim(mb_substr($normalized, 0, max(1, $maxLength - 3))).'...';
    }

    /**
     * @param  array{
     *     binary: string,
     *     mime_type: string,
     *     filename: string,
     *     mask_binary: string,
     *     mask_filename: string
     * }|null  $sourceImage
     * @return array{data_url: string, mime_type: string}
     */
    private function generateWithProvider(AiProvider $provider, string $prompt, ?array $sourceImage): array
    {
        $apiKey = trim((string) $provider->getConfigValue('api_key', ''));
        if ($apiKey === '') {
            throw new AiGenerationException('Clé API provider vide.');
        }

        $baseUrl = trim((string) $provider->getConfigValue('base_url', self::OPENAI_BASE_URL));
        if ($baseUrl === '') {
            $baseUrl = self::OPENAI_BASE_URL;
        }

        if (! str_starts_with(strtolower($baseUrl), 'https://')) {
            throw new AiGenerationException('La base URL du provider image doit utiliser HTTPS.');
        }

        $organization = trim((string) $provider->getConfigValue('organization', ''));

        $client = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(90)
            ->connectTimeout(10);

        if ($organization !== '') {
            $client = $client->withHeaders([
                'OpenAI-Organization' => $organization,
            ]);
        }

        $baseUrl = rtrim($baseUrl, '/');
        $models = $this->resolveImageModels($provider, $sourceImage !== null);
        $lastError = null;

        foreach ($models as $model) {
            $response = $this->sendImageRequest(
                $client,
                $baseUrl,
                $model,
                $prompt,
                $sourceImage
            );

            if (! $response->successful()) {
                $lastError = $this->extractProviderError($response);
                Log::warning('AI product image generation failed for model.', [
                    'provider' => $provider->code,
                    'model' => $model,
                    'error' => $lastError,
                ]);

                continue;
            }

            return $this->decodeGeneratedImagePayload($response);
        }

        throw new AiGenerationException(
            is_string($lastError) && $lastError !== ''
                ? $lastError
                : 'La génération d’image IA a échoué côté provider.'
        );
    }

    /**
     * @return list<string>
     */
    private function resolveImageModels(AiProvider $provider, bool $hasSourceImage): array
    {
        $configured = strtolower(trim((string) $provider->getConfigValue('model', '')));
        if ($configured === '') {
            throw new AiGenerationException(
                sprintf(
                    'Le provider "%s" est actif mais aucun modèle image n’est configuré.',
                    $provider->name
                )
            );
        }

        if (! $this->isSupportedImageModel($configured, $hasSourceImage)) {
            $suffix = $hasSourceImage
                ? 'Pour une édition avec image de référence, utilise un modèle GPT Image (gpt-image-1.5, gpt-image-1, gpt-image-1-mini) ou dall-e-2.'
                : 'Utilise un modèle image valide (gpt-image-1.5, gpt-image-1, gpt-image-1-mini, dall-e-3, dall-e-2).';

            throw new AiGenerationException(
                sprintf('Le modèle "%s" du provider "%s" n’est pas compatible image. %s', $configured, $provider->name, $suffix)
            );
        }

        return [$configured];
    }

    private function isGptImageModel(string $model): bool
    {
        return str_starts_with(strtolower(trim($model)), 'gpt-image-');
    }

    private function isSupportedImageModel(string $model, bool $hasSourceImage): bool
    {
        if ($this->isGptImageModel($model)) {
            return true;
        }

        if ($model === self::LEGACY_IMAGE_MODEL) {
            return true;
        }

        if (! $hasSourceImage && $model === self::ALT_IMAGE_MODEL) {
            return true;
        }

        return false;
    }

    /**
     * @param  array{
     *     binary: string,
     *     mime_type: string,
     *     filename: string,
     *     mask_binary: string,
     *     mask_filename: string
     * }|null  $sourceImage
     */
    private function sendImageRequest(
        \Illuminate\Http\Client\PendingRequest $client,
        string $baseUrl,
        string $model,
        string $prompt,
        ?array $sourceImage
    ): Response {
        if ($sourceImage !== null) {
            $payload = [
                'model' => $model,
                'prompt' => $prompt,
                'size' => '1024x1024',
                'n' => 1,
            ];

            if ($this->isGptImageModel($model)) {
                $payload['input_fidelity'] = 'high';

                $makeRequest = static function () use ($client, $sourceImage): \Illuminate\Http\Client\PendingRequest {
                    return $client
                        ->asMultipart()
                        ->attach(
                            'image[]',
                            $sourceImage['binary'],
                            $sourceImage['filename'],
                            ['Content-Type' => $sourceImage['mime_type']]
                        );
                };

                $response = $makeRequest()->post($baseUrl.'/images/edits', $payload);

                if ($this->isUnknownParameterError($response, 'input_fidelity')) {
                    unset($payload['input_fidelity']);
                    $response = $makeRequest()->post($baseUrl.'/images/edits', $payload);
                }

                return $response;
            }

            return $client
                ->asMultipart()
                ->attach(
                    'image',
                    $sourceImage['binary'],
                    $sourceImage['filename'],
                    ['Content-Type' => $sourceImage['mime_type']]
                )
                ->attach(
                    'mask',
                    $sourceImage['mask_binary'],
                    $sourceImage['mask_filename'],
                    ['Content-Type' => 'image/png']
                )
                ->post($baseUrl.'/images/edits', [
                    ...$payload,
                    'response_format' => 'b64_json',
                ]);
        }

        if ($this->isGptImageModel($model)) {
            return $client->post($baseUrl.'/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'size' => '1024x1024',
                'n' => 1,
            ]);
        }

        return $client->post($baseUrl.'/images/generations', [
            'model' => $model,
            'prompt' => $prompt,
            'size' => '1024x1024',
            'response_format' => 'b64_json',
            'n' => 1,
        ]);
    }

    private function isUnknownParameterError(Response $response, string $parameter): bool
    {
        if ($response->successful()) {
            return false;
        }

        $error = strtolower(trim($this->extractProviderError($response)));
        if ($error === '') {
            return false;
        }

        return str_contains($error, 'unknown parameter')
            && str_contains($error, strtolower($parameter));
    }

    /**
     * @return array{data_url: string, mime_type: string}
     */
    private function decodeGeneratedImagePayload(Response $response): array
    {
        $payload = $response->json();
        $b64Image = data_get($payload, 'data.0.b64_json');

        if (! is_string($b64Image) || trim($b64Image) === '') {
            throw new AiGenerationException('Réponse image IA invalide: image base64 introuvable.');
        }

        $binary = base64_decode($b64Image, true);

        if (! is_string($binary) || $binary === '') {
            throw new AiGenerationException('Réponse image IA invalide: base64 non décodable.');
        }

        if (strlen($binary) > self::MAX_IMAGE_BYTES) {
            throw new AiGenerationException('Image générée trop volumineuse (max 5MB).');
        }

        $mimeType = $this->detectMimeType($binary);

        return [
            'data_url' => 'data:'.$mimeType.';base64,'.base64_encode($binary),
            'mime_type' => $mimeType,
        ];
    }

    private function extractProviderError(Response $response): string
    {
        $json = $response->json();
        $message = data_get($json, 'error.message');

        if (! is_string($message) || trim($message) === '') {
            $message = 'La génération d’image IA a échoué côté provider.';
        }

        $normalized = strtolower(trim($message));
        if (
            str_contains($normalized, 'invalid value') &&
            str_contains($normalized, 'gpt-image-1') &&
            str_contains($normalized, 'dall-e-2')
        ) {
            return 'Le provider accepte uniquement dall-e-2 pour cet endpoint. Vérifie que tu utilises bien l’API OpenAI officielle, avec une clé/projet autorisés pour GPT Image.';
        }

        return $message;
    }

    private function detectMimeType(string $binary): string
    {
        $detected = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        $mimeType = is_string($detected) ? strtolower(trim($detected)) : '';

        $allowed = ['image/png', 'image/jpeg', 'image/webp'];
        if (! in_array($mimeType, $allowed, true)) {
            throw new AiGenerationException('Format d’image non supporté (PNG, JPEG, WEBP uniquement).');
        }

        return $mimeType;
    }
}
