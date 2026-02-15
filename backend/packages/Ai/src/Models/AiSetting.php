<?php

declare(strict_types=1);

namespace Omersia\Ai\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $scope
 * @property array<int, string>|null $usage_scopes
 * @property string|null $business_context
 * @property string|null $seo_objectives
 * @property string|null $forbidden_terms
 * @property string $writing_tone
 * @property string $content_locale
 * @property int $title_max_length
 * @property int $meta_description_max_length
 * @property string|null $additional_instructions
 */
class AiSetting extends Model
{
    public const GLOBAL_SCOPE = 'global';

    public const USAGE_ALL = 'all';

    public const USAGE_CATEGORY_CONTENT = 'category_content';

    public const USAGE_CATEGORY_SEO = 'category_seo';

    public const USAGE_PAGE_CONTENT = 'page_content';

    public const USAGE_PAGE_SEO = 'page_seo';

    public const USAGE_PRODUCT_CONTENT = 'product_content';

    public const USAGE_PRODUCT_SEO = 'product_seo';

    public const USAGE_ASSISTANT = 'assistant';

    protected $fillable = [
        'scope',
        'usage_scopes',
        'business_context',
        'seo_objectives',
        'forbidden_terms',
        'writing_tone',
        'content_locale',
        'title_max_length',
        'meta_description_max_length',
        'additional_instructions',
    ];

    protected $casts = [
        'usage_scopes' => 'array',
        'title_max_length' => 'integer',
        'meta_description_max_length' => 'integer',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function defaultValues(): array
    {
        return [
            'usage_scopes' => [self::USAGE_ALL],
            'business_context' => null,
            'seo_objectives' => null,
            'forbidden_terms' => null,
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => 160,
            'additional_instructions' => null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getUsageOptions(): array
    {
        return [
            self::USAGE_ALL => 'Tous les écrans IA',
            self::USAGE_CATEGORY_CONTENT => 'Page catégorie: contenu',
            self::USAGE_CATEGORY_SEO => 'Page catégorie: SEO',
            self::USAGE_PAGE_CONTENT => 'Pages CMS/e-commerce: contenu',
            self::USAGE_PAGE_SEO => 'Pages CMS/e-commerce: SEO',
            self::USAGE_PRODUCT_CONTENT => 'Produits: contenu',
            self::USAGE_PRODUCT_SEO => 'Produits: SEO',
            self::USAGE_ASSISTANT => 'Assistant backoffice',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function normalizeUsageScopesInput(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $allowed = array_keys(self::getUsageOptions());
        $normalized = [];

        foreach ($value as $scope) {
            if (! is_string($scope)) {
                continue;
            }

            $trimmed = trim($scope);
            if ($trimmed === '' || ! in_array($trimmed, $allowed, true)) {
                continue;
            }

            $normalized[] = $trimmed;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolveForUsage(string $usage): array
    {
        $global = self::query()->firstOrCreate(
            ['scope' => self::GLOBAL_SCOPE],
            self::defaultValues()
        );

        $resolved = self::mergeContextValues(
            self::defaultValues(),
            $global->toContextValues()
        );

        $contexts = self::query()
            ->where('scope', '!=', self::GLOBAL_SCOPE)
            ->orderBy('id')
            ->get();

        foreach ($contexts as $context) {
            if (! $context->appliesToUsage($usage)) {
                continue;
            }

            $resolved = self::mergeContextValues($resolved, $context->toContextValues());
        }

        return $resolved;
    }

    public function appliesToUsage(string $usage): bool
    {
        if ($this->scope === self::GLOBAL_SCOPE) {
            return true;
        }

        $usageScopes = $this->getUsageScopes();

        if ($usageScopes === []) {
            return false;
        }

        return in_array(self::USAGE_ALL, $usageScopes, true) || in_array($usage, $usageScopes, true);
    }

    /**
     * @return array<int, string>
     */
    public function getUsageScopes(): array
    {
        return self::normalizeUsageScopesInput($this->usage_scopes);
    }

    /**
     * @return array<string, mixed>
     */
    public function toContextValues(): array
    {
        return [
            'usage_scopes' => $this->getUsageScopes(),
            'business_context' => $this->business_context,
            'seo_objectives' => $this->seo_objectives,
            'forbidden_terms' => $this->forbidden_terms,
            'writing_tone' => $this->writing_tone,
            'content_locale' => $this->content_locale,
            'title_max_length' => (int) $this->title_max_length,
            'meta_description_max_length' => (int) $this->meta_description_max_length,
            'additional_instructions' => $this->additional_instructions,
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private static function mergeContextValues(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if ($key === 'usage_scopes') {
                continue;
            }

            if (in_array($key, ['title_max_length', 'meta_description_max_length'], true)) {
                if (is_numeric($value) && (int) $value > 0) {
                    $base[$key] = (int) $value;
                }

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $trimmed = trim($value);
            if ($trimmed === '') {
                continue;
            }

            $base[$key] = $trimmed;
        }

        return $base;
    }
}
