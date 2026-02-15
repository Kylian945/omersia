<?php

declare(strict_types=1);

namespace Omersia\Ai\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Omersia\Ai\Exceptions\AiGenerationException;
use Omersia\Ai\Models\AiProvider;
use Omersia\Ai\Models\AiSetting;
use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\OrderItem;
use Omersia\Catalog\Models\ProductTranslation;
use Omersia\Sales\Models\DiscountUsage;
use Throwable;

use function Laravel\Ai\agent;

class BackofficeAssistantService
{
    /**
     * @var array<string, int>
     */
    private const MONTH_NAME_TO_NUMBER = [
        'janvier' => 1,
        'janv' => 1,
        'january' => 1,
        'jan' => 1,
        'fevrier' => 2,
        'fev' => 2,
        'february' => 2,
        'feb' => 2,
        'mars' => 3,
        'march' => 3,
        'avril' => 4,
        'avr' => 4,
        'april' => 4,
        'apr' => 4,
        'mai' => 5,
        'may' => 5,
        'juin' => 6,
        'june' => 6,
        'juillet' => 7,
        'juil' => 7,
        'july' => 7,
        'jul' => 7,
        'aout' => 8,
        'august' => 8,
        'aug' => 8,
        'septembre' => 9,
        'sept' => 9,
        'september' => 9,
        'sep' => 9,
        'octobre' => 10,
        'oct' => 10,
        'october' => 10,
        'novembre' => 11,
        'nov' => 11,
        'november' => 11,
        'decembre' => 12,
        'dec' => 12,
        'december' => 12,
    ];

    /**
     * @var array<string, int>
     */
    private const WORD_TO_NUMBER = [
        'un' => 1,
        'une' => 1,
        'deux' => 2,
        'trois' => 3,
        'quatre' => 4,
        'cinq' => 5,
        'six' => 6,
        'sept' => 7,
        'huit' => 8,
        'neuf' => 9,
        'dix' => 10,
        'onze' => 11,
        'douze' => 12,
    ];

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @return array{reply: string}
     */
    public function ask(string $message, array $history = []): array
    {
        if (! function_exists('\Laravel\Ai\agent')) {
            throw new AiGenerationException(
                'Le SDK Laravel AI n\'est pas disponible. Installe d\'abord le package `laravel/ai`.'
            );
        }

        $message = trim($message);
        if ($message === '') {
            throw new AiGenerationException('La question ne peut pas être vide.');
        }

        $settings = AiSetting::resolveForUsage(AiSetting::USAGE_ASSISTANT);

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

        $sanitizedHistory = $this->sanitizeHistory($history);
        $intent = $this->resolveIntent($message);
        $period = $this->resolvePeriod($message, $intent);
        $analytics = $this->buildAnalyticsForIntent(
            $intent,
            $message,
            $period
        );
        $prompt = $this->buildPrompt($message, $sanitizedHistory, $analytics, $settings);

        $providers = $this->orderByDefaultProvider($providers);
        $lastError = null;

        foreach ($providers as $provider) {
            $this->applyRuntimeProviderConfig($provider);
            $model = trim((string) $provider->getConfigValue('model', ''));

            try {
                $response = agent()->prompt(
                    prompt: $prompt,
                    provider: $provider->code,
                    model: $model !== '' ? $model : null,
                    timeout: 45
                );

                $reply = $this->extractTextResponse($response);
                if ($reply === '') {
                    throw new AiGenerationException('Réponse IA vide.');
                }

                return [
                    'reply' => $reply,
                ];
            } catch (Throwable $e) {
                $lastError = $e;

                Log::warning('Backoffice assistant generation failed for provider.', [
                    'provider' => $provider->code,
                    'intent' => $intent,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new AiGenerationException(
            'L’assistant IA n’a pas pu générer de réponse. Vérifie les clés API, le modèle et le quota.',
            previous: $lastError
        );
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

    private function applyRuntimeProviderConfig(AiProvider $provider): void
    {
        $baseUrl = trim((string) $provider->getConfigValue('base_url', ''));
        $organization = trim((string) $provider->getConfigValue('organization', ''));
        $apiVersion = trim((string) $provider->getConfigValue('api_version', ''));

        config([
            'ai.default' => $provider->code,
            "ai.providers.{$provider->code}.driver" => $provider->getDriver(),
            "ai.providers.{$provider->code}.key" => (string) $provider->getConfigValue('api_key', ''),
            "ai.providers.{$provider->code}.url" => $baseUrl !== '' ? $baseUrl : null,
            "ai.providers.{$provider->code}.organization" => $organization !== '' ? $organization : null,
            "ai.providers.{$provider->code}.api_version" => $apiVersion !== '' ? $apiVersion : null,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function sanitizeHistory(array $history): array
    {
        $normalized = [];

        foreach ($history as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $role = strtolower(trim((string) ($entry['role'] ?? '')));
            if (! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $content = trim((string) ($entry['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $normalized[] = [
                'role' => $role,
                'content' => mb_substr($content, 0, 1000),
            ];
        }

        return array_slice($normalized, -12);
    }

    private function resolveIntent(string $message): string
    {
        $normalized = $this->normalizeQuestion($message);

        if (
            str_contains($normalized, 'panier moyen') ||
            str_contains($normalized, 'average order') ||
            str_contains($normalized, 'average basket')
        ) {
            return 'average_order_value';
        }

        if (
            str_contains($normalized, 'plus vendu') ||
            str_contains($normalized, 'meilleure vente') ||
            str_contains($normalized, 'top produit') ||
            str_contains($normalized, 'best seller')
        ) {
            return 'top_selling_product';
        }

        if (
            str_contains($normalized, 'code promo') ||
            str_contains($normalized, 'code reduction') ||
            str_contains($normalized, 'coupon')
        ) {
            return 'promo_code_usage';
        }

        return 'overview';
    }

    /**
     * @return array{start: Carbon, end: Carbon, label: string}
     */
    private function resolvePeriod(string $message, string $intent): array
    {
        $now = Carbon::now();
        $normalized = $this->normalizeQuestion($message);

        $specificMonth = $this->detectSpecificMonthPeriod($normalized, $now);
        if ($specificMonth !== null) {
            return $specificMonth;
        }

        if (preg_match('/\b(\d{1,2}|[a-z]+)\s+derniers?\s+jours\b/u', $normalized, $matches) === 1) {
            $days = $this->resolveNumberToken($matches[1] ?? null);
            if ($days !== null && $days > 0) {
                $start = $now->copy()->subDays($days)->startOfDay();
                $end = $now->copy()->endOfDay();

                return [
                    'start' => $start,
                    'end' => $end,
                    'label' => sprintf('%d derniers jours (%s au %s)', $days, $start->format('Y-m-d'), $end->format('Y-m-d')),
                ];
            }
        }

        if (preg_match('/\b(\d{1,2}|[a-z]+)\s+derniers?\s+mois\b/u', $normalized, $matches) === 1) {
            $months = $this->resolveNumberToken($matches[1] ?? null);
            if ($months !== null && $months > 0) {
                $start = $now->copy()->subMonthsNoOverflow($months)->startOfDay();
                $end = $now->copy()->endOfDay();

                return [
                    'start' => $start,
                    'end' => $end,
                    'label' => sprintf('%d derniers mois (%s au %s)', $months, $start->format('Y-m-d'), $end->format('Y-m-d')),
                ];
            }
        }

        if (str_contains($normalized, 'mois dernier')) {
            $start = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $end = $now->copy()->subMonthNoOverflow()->endOfMonth();

            return [
                'start' => $start,
                'end' => $end,
                'label' => sprintf('Mois dernier (%s au %s)', $start->format('Y-m-d'), $end->format('Y-m-d')),
            ];
        }

        if (
            str_contains($normalized, 'ce mois') ||
            str_contains($normalized, 'mois en cours')
        ) {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfDay();

            return [
                'start' => $start,
                'end' => $end,
                'label' => sprintf('Mois en cours (%s au %s)', $start->format('Y-m-d'), $end->format('Y-m-d')),
            ];
        }

        if (
            str_contains($normalized, 'cette annee') ||
            str_contains($normalized, 'annee en cours')
        ) {
            $start = $now->copy()->startOfYear();
            $end = $now->copy()->endOfDay();

            return [
                'start' => $start,
                'end' => $end,
                'label' => sprintf('Année en cours (%s au %s)', $start->format('Y-m-d'), $end->format('Y-m-d')),
            ];
        }

        return match ($intent) {
            'average_order_value' => [
                'start' => $now->copy()->subMonthsNoOverflow(2)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => sprintf(
                    '2 derniers mois (%s au %s)',
                    $now->copy()->subMonthsNoOverflow(2)->startOfDay()->format('Y-m-d'),
                    $now->copy()->endOfDay()->format('Y-m-d')
                ),
            ],
            'promo_code_usage' => [
                'start' => $now->copy()->subMonthsNoOverflow(3)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => sprintf(
                    '3 derniers mois (%s au %s)',
                    $now->copy()->subMonthsNoOverflow(3)->startOfDay()->format('Y-m-d'),
                    $now->copy()->endOfDay()->format('Y-m-d')
                ),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfDay(),
                'label' => sprintf(
                    'Mois en cours (%s au %s)',
                    $now->copy()->startOfMonth()->format('Y-m-d'),
                    $now->copy()->endOfDay()->format('Y-m-d')
                ),
            ],
        };
    }

    /**
     * @return array{start: Carbon, end: Carbon, label: string}|null
     */
    private function detectSpecificMonthPeriod(string $normalizedQuestion, Carbon $now): ?array
    {
        $monthPattern = implode('|', array_map(
            static fn (string $month): string => preg_quote($month, '/'),
            array_keys(self::MONTH_NAME_TO_NUMBER)
        ));

        if (preg_match('/\b('.$monthPattern.')\b(?:\s+(\d{4}))?/u', $normalizedQuestion, $matches) !== 1) {
            return null;
        }

        $monthAlias = $matches[1] ?? null;
        if (! is_string($monthAlias) || $monthAlias === '') {
            return null;
        }

        $monthNumber = self::MONTH_NAME_TO_NUMBER[$monthAlias] ?? null;
        if (! is_int($monthNumber)) {
            return null;
        }

        $providedYear = $matches[2] ?? null;
        $year = null;

        if (is_string($providedYear) && preg_match('/^\d{4}$/', $providedYear) === 1) {
            $year = (int) $providedYear;
        } else {
            $year = $now->year;

            if ($monthNumber > (int) $now->month) {
                $year--;
            }
        }

        $start = Carbon::create($year, $monthNumber, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [
            'start' => $start,
            'end' => $end,
            'label' => sprintf('Mois %s %d (%s au %s)', ucfirst($start->locale('fr')->translatedFormat('F')), $year, $start->format('Y-m-d'), $end->format('Y-m-d')),
        ];
    }

    private function resolveNumberToken(mixed $token): ?int
    {
        if (is_numeric($token)) {
            return (int) $token;
        }

        if (! is_string($token)) {
            return null;
        }

        $normalized = strtolower(trim($token));
        if ($normalized === '') {
            return null;
        }

        return self::WORD_TO_NUMBER[$normalized] ?? null;
    }

    /**
     * @param  array{start: Carbon, end: Carbon, label: string}  $period
     * @return array<string, mixed>
     */
    private function buildAnalyticsForIntent(string $intent, string $message, array $period): array
    {
        return match ($intent) {
            'top_selling_product' => $this->buildTopSellingProductAnalytics($period),
            'average_order_value' => $this->buildAverageOrderValueAnalytics($period),
            'promo_code_usage' => $this->buildPromoCodeAnalytics($period, $this->resolvePromoCode($message)),
            default => $this->buildOverviewAnalytics($period, $message),
        };
    }

    /**
     * @param  array{start: Carbon, end: Carbon, label: string}  $period
     * @return array<string, mixed>
     */
    private function buildTopSellingProductAnalytics(array $period): array
    {
        $start = $period['start'];
        $end = $period['end'];

        $baseOrderItemsQuery = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.placed_at', [$start, $end])
            ->where('orders.status', '!=', 'draft')
            ->where('orders.payment_status', 'paid');

        $topProduct = (clone $baseOrderItemsQuery)
            ->selectRaw('order_items.product_id as product_id')
            ->selectRaw('MAX(order_items.name) as fallback_name')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold')
            ->selectRaw('SUM(order_items.total_price) as revenue')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ->groupBy('order_items.product_id')
            ->orderByDesc('quantity_sold')
            ->orderByDesc('revenue')
            ->first();

        $topProductPayload = null;

        if ($topProduct !== null) {
            $productId = is_numeric($topProduct->product_id ?? null) ? (int) $topProduct->product_id : null;
            $productName = trim((string) ($topProduct->fallback_name ?? ''));

            if ($productId !== null) {
                $productName = $this->resolveProductName($productId, $productName);
            }

            $topProductPayload = [
                'product_id' => $productId,
                'name' => $productName !== '' ? $productName : 'Produit non identifié',
                'quantity_sold' => (int) ($topProduct->quantity_sold ?? 0),
                'revenue' => round((float) ($topProduct->revenue ?? 0), 2),
                'orders_count' => (int) ($topProduct->orders_count ?? 0),
            ];
        }

        $ordersSummaryQuery = $this->paidConfirmedOrdersInPeriod($start, $end);
        $ordersCount = (int) $ordersSummaryQuery->count();
        $revenue = (float) $ordersSummaryQuery->sum('total');
        $itemsSold = (int) (clone $baseOrderItemsQuery)->sum('order_items.quantity');

        return [
            'intent' => 'top_selling_product',
            'period' => $this->formatPeriodContext($period),
            'top_product' => $topProductPayload,
            'totals' => [
                'orders_count' => $ordersCount,
                'items_sold' => $itemsSold,
                'revenue' => round($revenue, 2),
            ],
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon, label: string}  $period
     * @return array<string, mixed>
     */
    private function buildAverageOrderValueAnalytics(array $period): array
    {
        $start = $period['start'];
        $end = $period['end'];

        $ordersQuery = $this->paidConfirmedOrdersInPeriod($start, $end);
        $ordersCount = (int) $ordersQuery->count();
        $revenue = (float) $ordersQuery->sum('total');
        $average = $ordersCount > 0 ? $revenue / $ordersCount : 0.0;

        $currency = (string) (Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->where('status', '!=', 'draft')
            ->where('payment_status', 'paid')
            ->whereNotNull('currency')
            ->value('currency') ?? 'EUR');

        $monthlyBreakdown = [];
        $cursor = $start->copy()->startOfMonth();
        $maxIterations = 12;

        while ($cursor->lessThanOrEqualTo($end) && $maxIterations > 0) {
            $segmentStart = $cursor->copy()->startOfMonth();
            $segmentEnd = $cursor->copy()->endOfMonth();

            if ($segmentStart->lessThan($start)) {
                $segmentStart = $start->copy();
            }

            if ($segmentEnd->greaterThan($end)) {
                $segmentEnd = $end->copy();
            }

            $segmentQuery = $this->paidConfirmedOrdersInPeriod($segmentStart, $segmentEnd);
            $segmentOrdersCount = (int) $segmentQuery->count();
            $segmentRevenue = (float) $segmentQuery->sum('total');

            $monthlyBreakdown[] = [
                'label' => $cursor->copy()->locale('fr')->translatedFormat('F Y'),
                'start' => $segmentStart->format('Y-m-d'),
                'end' => $segmentEnd->format('Y-m-d'),
                'orders_count' => $segmentOrdersCount,
                'revenue' => round($segmentRevenue, 2),
                'average_order_value' => $segmentOrdersCount > 0 ? round($segmentRevenue / $segmentOrdersCount, 2) : 0.0,
            ];

            $cursor->addMonthNoOverflow()->startOfMonth();
            $maxIterations--;
        }

        return [
            'intent' => 'average_order_value',
            'period' => $this->formatPeriodContext($period),
            'currency' => strtoupper(trim($currency)) !== '' ? strtoupper(trim($currency)) : 'EUR',
            'orders_count' => $ordersCount,
            'revenue' => round($revenue, 2),
            'average_order_value' => round($average, 2),
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon, label: string}  $period
     * @return array<string, mixed>
     */
    private function buildPromoCodeAnalytics(array $period, ?string $requestedCode): array
    {
        $start = $period['start'];
        $end = $period['end'];
        $dateExpression = DB::raw('COALESCE(orders.placed_at, discount_usages.created_at)');

        $baseQuery = DiscountUsage::query()
            ->join('discounts', 'discounts.id', '=', 'discount_usages.discount_id')
            ->leftJoin('orders', 'orders.id', '=', 'discount_usages.order_id')
            ->where('discounts.method', '=', 'code')
            ->whereBetween($dateExpression, [$start, $end]);

        $topCodes = (clone $baseQuery)
            ->selectRaw('UPPER(discounts.code) as code')
            ->selectRaw('SUM(discount_usages.usage_count) as usage_count')
            ->selectRaw('COUNT(DISTINCT discount_usages.order_id) as orders_count')
            ->groupBy('discounts.code')
            ->orderByDesc('usage_count')
            ->limit(8)
            ->get()
            ->map(static function ($row): array {
                return [
                    'code' => trim((string) ($row->code ?? '')),
                    'usage_count' => (int) ($row->usage_count ?? 0),
                    'orders_count' => (int) ($row->orders_count ?? 0),
                ];
            })
            ->values()
            ->all();

        $requestedCodeUsage = null;
        if (is_string($requestedCode) && $requestedCode !== '') {
            $row = (clone $baseQuery)
                ->whereRaw('LOWER(discounts.code) = ?', [mb_strtolower($requestedCode)])
                ->selectRaw('UPPER(discounts.code) as code')
                ->selectRaw('SUM(discount_usages.usage_count) as usage_count')
                ->selectRaw('COUNT(DISTINCT discount_usages.order_id) as orders_count')
                ->groupBy('discounts.code')
                ->first();

            $requestedCodeUsage = [
                'code' => strtoupper($requestedCode),
                'usage_count' => (int) ($row->usage_count ?? 0),
                'orders_count' => (int) ($row->orders_count ?? 0),
                'used' => $row !== null && (int) ($row->usage_count ?? 0) > 0,
            ];
        }

        $totalUsages = (int) (clone $baseQuery)->sum('discount_usages.usage_count');

        return [
            'intent' => 'promo_code_usage',
            'period' => $this->formatPeriodContext($period),
            'requested_code' => $requestedCodeUsage,
            'total_usage_count' => $totalUsages,
            'top_codes' => $topCodes,
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon, label: string}  $period
     * @return array<string, mixed>
     */
    private function buildOverviewAnalytics(array $period, string $message): array
    {
        $topSellingProduct = $this->buildTopSellingProductAnalytics($period);
        $averageOrderValue = $this->buildAverageOrderValueAnalytics($period);
        $promoCodeUsage = $this->buildPromoCodeAnalytics($period, $this->resolvePromoCode($message));

        return [
            'intent' => 'overview',
            'period' => $this->formatPeriodContext($period),
            'summary' => [
                'top_selling_product' => $topSellingProduct['top_product'] ?? null,
                'sales_totals' => $topSellingProduct['totals'] ?? null,
                'average_order_value' => $averageOrderValue['average_order_value'] ?? null,
                'orders_count' => $averageOrderValue['orders_count'] ?? null,
                'top_promo_codes' => $promoCodeUsage['top_codes'] ?? [],
            ],
            'details' => [
                'top_selling_product' => $topSellingProduct,
                'average_order_value' => $averageOrderValue,
                'promo_code_usage' => $promoCodeUsage,
            ],
        ];
    }

    private function resolveProductName(int $productId, string $fallbackName = ''): string
    {
        $preferredLocales = array_values(array_unique(array_filter([
            app()->getLocale(),
            'fr',
            'en',
        ])));

        foreach ($preferredLocales as $locale) {
            $translation = ProductTranslation::query()
                ->where('product_id', $productId)
                ->where('locale', $locale)
                ->first();

            if ($translation instanceof ProductTranslation && is_string($translation->name) && trim($translation->name) !== '') {
                return trim((string) $translation->name);
            }
        }

        $anyTranslation = ProductTranslation::query()
            ->where('product_id', $productId)
            ->first();

        if ($anyTranslation instanceof ProductTranslation && is_string($anyTranslation->name) && trim($anyTranslation->name) !== '') {
            return trim((string) $anyTranslation->name);
        }

        if (trim($fallbackName) !== '') {
            return trim($fallbackName);
        }

        return 'Produit #'.$productId;
    }

    private function resolvePromoCode(string $message): ?string
    {
        if (
            preg_match('/code(?:\s+promo|\s+de\s+reduction)?\s+["“\'`]?([A-Za-z0-9_-]{2,30})["”\'`]?/u', $message, $matches) === 1
        ) {
            $code = strtoupper(trim((string) ($matches[1] ?? '')));

            return $code !== '' ? $code : null;
        }

        return null;
    }

    /**
     * @param  array{start: Carbon, end: Carbon, label: string}  $period
     * @return array{label: string, start: string, end: string}
     */
    private function formatPeriodContext(array $period): array
    {
        return [
            'label' => $period['label'],
            'start' => $period['start']->format('Y-m-d'),
            'end' => $period['end']->format('Y-m-d'),
        ];
    }

    /**
     * @return Builder<Order>
     */
    private function paidConfirmedOrdersInPeriod(Carbon $start, Carbon $end): Builder
    {
        return Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->where('status', '!=', 'draft')
            ->where('payment_status', 'paid');
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @param  array<string, mixed>  $analytics
     */
    private function buildPrompt(string $message, array $history, array $analytics, array $settings): string
    {
        $context = [
            'business_context' => $settings['business_context'] ?? null,
            'writing_tone' => $settings['writing_tone'] ?? 'professionnel',
            'content_locale' => $settings['content_locale'] ?? 'fr',
            'additional_instructions' => $settings['additional_instructions'] ?? null,
        ];

        $jsonContext = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $jsonHistory = json_encode($history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $jsonAnalytics = json_encode($analytics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if (! is_string($jsonContext)) {
            $jsonContext = '{}';
        }

        if (! is_string($jsonHistory)) {
            $jsonHistory = '[]';
        }

        if (! is_string($jsonAnalytics)) {
            $jsonAnalytics = '{}';
        }

        return <<<PROMPT
Tu es l'assistant analytique du back-office Omersia.
Tu réponds en français, de manière claire et concise, uniquement sur la base des données fournies.

Règles impératives :
- N'invente jamais de chiffres.
- Utilise uniquement "Données analytiques disponibles".
- Mentionne explicitement la période utilisée (dates incluses).
- Si les données sont insuffisantes, dis-le clairement et propose une prochaine question utile.
- N'expose jamais de secrets, clés API, SQL ou détails techniques internes.

Contexte global :
{$jsonContext}

Historique conversation (chronologique) :
{$jsonHistory}

Données analytiques disponibles :
{$jsonAnalytics}

Question utilisateur :
{$message}

Format de réponse attendu :
1) Réponse directe
2) Détails chiffrés (si disponibles)
3) Prochaine question suggérée (une seule phrase)
PROMPT;
    }

    private function extractTextResponse(mixed $response): string
    {
        if (is_object($response)) {
            if (isset($response->text) && is_string($response->text)) {
                return trim($response->text);
            }

            if (method_exists($response, 'text')) {
                $value = $response->text();

                if (is_string($value)) {
                    return trim($value);
                }
            }
        }

        if (is_string($response)) {
            return trim($response);
        }

        throw new AiGenerationException('Réponse IA invalide: texte introuvable.');
    }

    private function normalizeQuestion(string $message): string
    {
        return mb_strtolower(trim(Str::ascii($message)));
    }
}
