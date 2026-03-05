<?php

declare(strict_types=1);

namespace Omersia\CMS\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Omersia\CMS\Models\PageTranslation;
use Omersia\CMS\Models\PageVersion;

class PageVersioningService
{
    public function createSnapshot(
        PageTranslation $translation,
        ?int $actorId = null,
        ?string $label = null
    ): PageVersion {
        return PageVersion::query()->create([
            'page_translation_id' => $translation->id,
            'content_json' => $translation->content_json,
            'created_by' => $actorId,
            'label' => $label ?? $this->defaultSnapshotLabel(),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, PageVersion>
     */
    public function getVersions(PageTranslation $translation, int $perPage = 20): LengthAwarePaginator
    {
        return $translation->versions()
            ->with('creator')
            ->paginate($perPage);
    }

    public function restoreVersion(
        PageTranslation $translation,
        PageVersion $version,
        ?int $actorId = null
    ): PageTranslation {
        if ((int) $version->page_translation_id !== (int) $translation->id) {
            throw new \InvalidArgumentException('Version non liée à cette traduction.');
        }

        if (is_array($translation->content_json)) {
            $this->createSnapshot(
                $translation,
                $actorId,
                'Sauvegarde auto avant restauration '.now()->format('d/m/Y H:i')
            );
        }

        $translation->update([
            'content_json' => $version->content_json,
        ]);

        $this->flushApiCache();

        return $translation->fresh() ?? $translation;
    }

    public function contentChanged(?array $current, ?array $next): bool
    {
        return $this->normalizeForComparison($current) !== $this->normalizeForComparison($next);
    }

    /**
     * @param  array<string, mixed>|null  $from
     * @param  array<string, mixed>|null  $to
     * @return array{
     *     added: list<array{path: string, value: string}>,
     *     removed: list<array{path: string, value: string}>,
     *     changed: list<array{path: string, old: string, new: string}>,
     *     has_changes: bool
     * }
     */
    public function buildVisualDiff(?array $from, ?array $to): array
    {
        $fromFlat = $this->flattenArray($from ?? []);
        $toFlat = $this->flattenArray($to ?? []);

        $added = [];
        $removed = [];
        $changed = [];

        foreach ($toFlat as $path => $value) {
            if (! array_key_exists($path, $fromFlat)) {
                $added[] = ['path' => $path, 'value' => $value];
                continue;
            }

            if ($fromFlat[$path] !== $value) {
                $changed[] = [
                    'path' => $path,
                    'old' => $fromFlat[$path],
                    'new' => $value,
                ];
            }
        }

        foreach ($fromFlat as $path => $value) {
            if (! array_key_exists($path, $toFlat)) {
                $removed[] = ['path' => $path, 'value' => $value];
            }
        }

        usort($added, fn (array $a, array $b): int => strcmp($a['path'], $b['path']));
        usort($removed, fn (array $a, array $b): int => strcmp($a['path'], $b['path']));
        usort($changed, fn (array $a, array $b): int => strcmp($a['path'], $b['path']));

        return [
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
            'has_changes' => $added !== [] || $removed !== [] || $changed !== [],
        ];
    }

    private function defaultSnapshotLabel(): string
    {
        return 'Sauvegarde auto '.now()->format('d/m/Y H:i');
    }

    private function normalizeForComparison(?array $content): string
    {
        if ($content === null) {
            return 'null';
        }

        return json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
    }

    /**
     * @param  array<string|int, mixed>  $data
     * @return array<string, string>
     */
    private function flattenArray(array $data, string $prefix = ''): array
    {
        if ($data === []) {
            return [
                $prefix === '' ? '$' : $prefix => '[]',
            ];
        }

        $flat = [];
        foreach ($data as $key => $value) {
            $segment = is_int($key) ? "[{$key}]" : (string) $key;
            $path = $prefix === ''
                ? (is_int($key) ? $segment : $segment)
                : (is_int($key) ? "{$prefix}{$segment}" : "{$prefix}.{$segment}");

            if (is_array($value)) {
                $flat += $this->flattenArray($value, $path);
                continue;
            }

            if (is_bool($value)) {
                $flat[$path] = $value ? 'true' : 'false';
                continue;
            }

            if ($value === null) {
                $flat[$path] = 'null';
                continue;
            }

            if (is_scalar($value)) {
                $flat[$path] = (string) $value;
                continue;
            }

            $flat[$path] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return $flat;
    }

    private function flushApiCache(): void
    {
        Cache::tags(['pages'])->flush();
        Cache::tags(['menus'])->flush();
    }
}
