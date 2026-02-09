<?php

declare(strict_types=1);

namespace Omersia\Apparence\Console\Commands;

use Illuminate\Console\Command;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Services\ThemeCustomizationService;

class SyncThemeSchema extends Command
{
    protected $signature = 'theme:sync-schema {--theme= : Slug du thème à synchroniser (défaut: vision)}';

    protected $description = 'Synchronise le settings_schema d\'un thème depuis son fichier JSON';

    public function __construct(
        protected ThemeCustomizationService $customizationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $themeSlug = $this->option('theme') ?? 'vision';

        $theme = Theme::where('slug', $themeSlug)->first();

        if (! $theme) {
            $this->error("Thème '{$themeSlug}' non trouvé.");

            return self::FAILURE;
        }

        // Load theme configuration from JSON file
        $configPath = storage_path("app/theme-{$themeSlug}.json");

        if (! file_exists($configPath)) {
            $this->error("Fichier de configuration non trouvé: {$configPath}");

            return self::FAILURE;
        }

        $config = json_decode(file_get_contents($configPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Erreur de parsing JSON: '.json_last_error_msg());

            return self::FAILURE;
        }

        if (! isset($config['settings_schema'])) {
            $this->error('Le fichier de configuration ne contient pas de settings_schema.');

            return self::FAILURE;
        }

        // Update theme settings_schema
        $theme->settings_schema = $config['settings_schema'];
        $theme->save();

        $this->info("Settings schema mis à jour pour le thème '{$themeSlug}'.");

        // Ask if we should reinitialize settings
        if ($this->confirm('Voulez-vous réinitialiser les paramètres du thème avec les nouvelles valeurs par défaut?', false)) {
            // Delete existing settings
            $theme->settings()->delete();

            // Reinitialize with new schema defaults
            $this->customizationService->initializeDefaultSettings($theme);

            $this->info('Paramètres du thème réinitialisés.');
        }

        return self::SUCCESS;
    }
}
