<?php

declare(strict_types=1);

namespace Omersia\Apparence\Services;

use Omersia\Apparence\Models\EcommercePage;
use Omersia\Apparence\Models\Theme;

class ThemeWidgetService
{
    /**
     * Compare widgets between current active theme and a new theme
     * Returns information about incompatible widgets
     */
    public function compareThemeWidgets(Theme $currentTheme, Theme $newTheme): array
    {
        $currentWidgetTypes = $currentTheme->getWidgetTypes();
        $newWidgetTypes = $newTheme->getWidgetTypes();

        // Widgets that will be removed (exist in current but not in new)
        $removedWidgets = array_diff($currentWidgetTypes, $newWidgetTypes);

        // Widgets that will be added (exist in new but not in current)
        $addedWidgets = array_diff($newWidgetTypes, $currentWidgetTypes);

        // Find pages using removed widgets
        $affectedPages = $this->findPagesUsingWidgets($currentTheme->shop_id, $removedWidgets);

        return [
            'has_incompatibilities' => count($removedWidgets) > 0,
            'removed_widgets' => $this->getWidgetDetails($currentTheme, $removedWidgets),
            'added_widgets' => $this->getWidgetDetails($newTheme, $addedWidgets),
            'affected_pages' => $affectedPages,
            'total_widgets_to_remove' => $this->countWidgetsInPages($affectedPages, $removedWidgets),
        ];
    }

    /**
     * Get widget details from theme config
     */
    protected function getWidgetDetails(Theme $theme, array $widgetTypes): array
    {
        $widgets = $theme->getWidgets();

        return array_filter($widgets, function ($widget) use ($widgetTypes) {
            return in_array($widget['type'] ?? '', $widgetTypes);
        });
    }

    /**
     * Find all pages using specific widget types
     */
    protected function findPagesUsingWidgets(int $shopId, array $widgetTypes): array
    {
        if (empty($widgetTypes)) {
            return [];
        }

        $pages = EcommercePage::where('shop_id', $shopId)
            ->with('translations')
            ->get();

        $affectedPages = [];

        foreach ($pages as $page) {
            foreach ($page->translations as $translation) {
                $content = $translation->content_json ?? [];
                $usedWidgets = $this->extractWidgetTypesFromContent($content);

                $incompatibleWidgets = array_intersect($usedWidgets, $widgetTypes);

                if (! empty($incompatibleWidgets)) {
                    $affectedPages[] = [
                        'page_id' => $page->id,
                        'page_type' => $page->type,
                        'page_slug' => $page->slug,
                        'locale' => $translation->locale,
                        'title' => $translation->title,
                        'incompatible_widgets' => array_values($incompatibleWidgets),
                    ];
                }
            }
        }

        return $affectedPages;
    }

    /**
     * Extract all widget types used in page content
     */
    protected function extractWidgetTypesFromContent(array $content): array
    {
        $widgetTypes = [];

        if (isset($content['sections']) && is_array($content['sections'])) {
            foreach ($content['sections'] as $section) {
                $widgetTypes = array_merge(
                    $widgetTypes,
                    $this->extractWidgetTypesFromColumns($section['columns'] ?? [])
                );
            }
        }

        return array_unique($widgetTypes);
    }

    /**
     * Recursively extract widget types from columns
     */
    protected function extractWidgetTypesFromColumns(array $columns): array
    {
        $widgetTypes = [];

        foreach ($columns as $column) {
            // Extract from widgets in this column
            if (isset($column['widgets']) && is_array($column['widgets'])) {
                foreach ($column['widgets'] as $widget) {
                    if (isset($widget['type'])) {
                        $widgetTypes[] = $widget['type'];
                    }
                }
            }

            // Recursively extract from nested columns
            if (isset($column['columns']) && is_array($column['columns'])) {
                $widgetTypes = array_merge(
                    $widgetTypes,
                    $this->extractWidgetTypesFromColumns($column['columns'])
                );
            }
        }

        return $widgetTypes;
    }

    /**
     * Count total number of widget instances to be removed
     */
    protected function countWidgetsInPages(array $affectedPages, array $widgetTypes): int
    {
        $count = 0;

        foreach ($affectedPages as $pageInfo) {
            $count += count($pageInfo['incompatible_widgets']);
        }

        return $count;
    }

    /**
     * Remove incompatible widgets from all pages
     */
    public function cleanIncompatibleWidgets(int $shopId, array $widgetTypesToRemove): int
    {
        if (empty($widgetTypesToRemove)) {
            return 0;
        }

        $pages = EcommercePage::where('shop_id', $shopId)
            ->with('translations')
            ->get();

        $removedCount = 0;

        foreach ($pages as $page) {
            foreach ($page->translations as $translation) {
                $content = $translation->content_json ?? [];
                $cleanedContent = $this->removeWidgetsFromContent($content, $widgetTypesToRemove);

                if ($content !== $cleanedContent) {
                    $translation->content_json = $cleanedContent;
                    $translation->save();
                    $removedCount++;
                }
            }
        }

        return $removedCount;
    }

    /**
     * Remove specific widget types from content
     */
    protected function removeWidgetsFromContent(array $content, array $widgetTypesToRemove): array
    {
        if (isset($content['sections']) && is_array($content['sections'])) {
            foreach ($content['sections'] as $sectionIndex => $section) {
                if (isset($section['columns'])) {
                    $content['sections'][$sectionIndex]['columns'] =
                        $this->removeWidgetsFromColumns($section['columns'], $widgetTypesToRemove);
                }
            }
        }

        return $content;
    }

    /**
     * Recursively remove widgets from columns
     */
    protected function removeWidgetsFromColumns(array $columns, array $widgetTypesToRemove): array
    {
        foreach ($columns as $columnIndex => $column) {
            // Remove widgets from this column
            if (isset($column['widgets']) && is_array($column['widgets'])) {
                $columns[$columnIndex]['widgets'] = array_values(
                    array_filter($column['widgets'], function ($widget) use ($widgetTypesToRemove) {
                        return ! in_array($widget['type'] ?? '', $widgetTypesToRemove);
                    })
                );
            }

            // Recursively clean nested columns
            if (isset($column['columns']) && is_array($column['columns'])) {
                $columns[$columnIndex]['columns'] =
                    $this->removeWidgetsFromColumns($column['columns'], $widgetTypesToRemove);
            }
        }

        return $columns;
    }
}
