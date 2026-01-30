<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Theme-related OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="ThemeSettingsResponse",
 *     type="object",
 *     description="Active theme settings and customization",
 *
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         description="Theme settings object",
 *         example={"primary_color": "#3B82F6", "font_family": "Inter"}
 *     ),
 *     @OA\Property(
 *         property="settings_schema",
 *         type="object",
 *         nullable=true,
 *         description="Theme settings schema definition"
 *     ),
 *     @OA\Property(
 *         property="css_variables",
 *         type="object",
 *         description="CSS variables for theme customization",
 *         example={"--color-primary": "#3B82F6", "--font-body": "Inter"}
 *     ),
 *     @OA\Property(property="component_path", type="string", example="vision", description="Theme component directory"),
 *     @OA\Property(property="theme_slug", type="string", example="vision", description="Active theme slug")
 * )
 */
class ThemeSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
