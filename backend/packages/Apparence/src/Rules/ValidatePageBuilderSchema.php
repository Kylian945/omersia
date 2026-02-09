<?php

declare(strict_types=1);

namespace Omersia\Apparence\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidatePageBuilderSchema implements Rule
{
    private string $errorMessage = '';

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        // Decode JSON if it's a string
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errorMessage = 'Le contenu JSON est invalide.';

                return false;
            }
            $value = $decoded;
        }

        // Must be an array
        if (! is_array($value)) {
            $this->errorMessage = 'Le contenu doit être un tableau valide.';

            return false;
        }

        // Check for native content structure (category/product pages)
        if (isset($value['beforeNative']) || isset($value['afterNative'])) {
            return $this->validateNativeContentStructure($value);
        }

        // Standard page structure
        return $this->validateStandardStructure($value);
    }

    /**
     * Validate native content structure (beforeNative/afterNative).
     */
    private function validateNativeContentStructure(array $content): bool
    {
        if (isset($content['beforeNative'])) {
            if (! is_array($content['beforeNative'])) {
                $this->errorMessage = 'La section beforeNative doit être un tableau.';

                return false;
            }

            if (! $this->validateSectionsArray($content['beforeNative'])) {
                return false;
            }
        }

        if (isset($content['afterNative'])) {
            if (! is_array($content['afterNative'])) {
                $this->errorMessage = 'La section afterNative doit être un tableau.';

                return false;
            }

            if (! $this->validateSectionsArray($content['afterNative'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate standard structure (sections array).
     */
    private function validateStandardStructure(array $content): bool
    {
        return $this->validateSectionsArray($content);
    }

    /**
     * Validate sections array structure.
     */
    private function validateSectionsArray(array $content): bool
    {
        // Must have sections array
        if (! isset($content['sections'])) {
            $this->errorMessage = 'Le contenu doit contenir un tableau "sections".';

            return false;
        }

        if (! is_array($content['sections'])) {
            $this->errorMessage = 'La propriété "sections" doit être un tableau.';

            return false;
        }

        // Validate each section
        foreach ($content['sections'] as $index => $section) {
            if (! is_array($section)) {
                $this->errorMessage = "La section #{$index} doit être un tableau.";

                return false;
            }

            // Validate columns if present
            if (isset($section['columns'])) {
                if (! is_array($section['columns'])) {
                    $this->errorMessage = "La section #{$index} doit contenir un tableau de colonnes.";

                    return false;
                }

                if (! $this->validateColumns($section['columns'], $index)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate columns array and their width properties.
     */
    private function validateColumns(array $columns, int $sectionIndex, int $depth = 0): bool
    {
        foreach ($columns as $colIndex => $column) {
            if (! is_array($column)) {
                $this->errorMessage = "La colonne #{$colIndex} de la section #{$sectionIndex} doit être un tableau.";

                return false;
            }

            // Validate desktopWidth if present
            if (isset($column['desktopWidth'])) {
                if (! $this->validateWidth($column['desktopWidth'], 'desktopWidth', $sectionIndex, $colIndex)) {
                    return false;
                }
            }

            // Validate mobileWidth if present
            if (isset($column['mobileWidth'])) {
                if (! $this->validateWidth($column['mobileWidth'], 'mobileWidth', $sectionIndex, $colIndex)) {
                    return false;
                }
            }

            // Recursively validate nested columns (max depth to prevent infinite recursion)
            if (isset($column['columns']) && $depth < 10) {
                if (! is_array($column['columns'])) {
                    $this->errorMessage = "Les colonnes imbriquées de la colonne #{$colIndex} doivent être un tableau.";

                    return false;
                }

                if (! $this->validateColumns($column['columns'], $sectionIndex, $depth + 1)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate width value (must be numeric between 0 and 100).
     */
    private function validateWidth(mixed $value, string $fieldName, int $sectionIndex, int $colIndex): bool
    {
        // Must be numeric
        if (! is_numeric($value)) {
            $this->errorMessage = "Le champ {$fieldName} de la colonne #{$colIndex} (section #{$sectionIndex}) doit être numérique.";

            return false;
        }

        // Cast to float for validation
        $numericValue = (float) $value;

        // Must be between 0 and 100
        if ($numericValue < 0 || $numericValue > 100) {
            $this->errorMessage = "Le champ {$fieldName} de la colonne #{$colIndex} (section #{$sectionIndex}) doit être entre 0 et 100.";

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->errorMessage ?: 'Le schéma du page builder est invalide.';
    }
}
