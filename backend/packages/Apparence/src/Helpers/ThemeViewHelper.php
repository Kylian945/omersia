<?php

declare(strict_types=1);

namespace Omersia\Apparence\Helpers;

use Illuminate\Support\Facades\View;

class ThemeViewHelper
{
    /**
     * Get the widget view path for the active theme
     * Falls back to vision theme if theme doesn't have it
     */
    public static function getWidgetView(string $widgetType, ?string $themeSlug = null): string
    {
        // Default to vision if no theme specified
        $themeSlug = $themeSlug ?: 'vision';

        $themeView = "apparence::themes.{$themeSlug}.builder-widgets.{$widgetType}";

        // Check if theme has this widget view
        if (View::exists($themeView)) {
            return $themeView;
        }

        // Fallback to vision theme if not the current theme
        if ($themeSlug !== 'vision') {
            return "apparence::themes.vision.builder-widgets.{$widgetType}";
        }

        // If vision itself doesn't have it, return the vision view path anyway
        // This will cause an error which is better than silently failing
        return $themeView;
    }

    /**
     * Check if a theme has a specific widget view
     */
    public static function themeHasWidgetView(string $widgetType, string $themeSlug): bool
    {
        return View::exists("apparence::themes.{$themeSlug}.builder-widgets.{$widgetType}");
    }
}
