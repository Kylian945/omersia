<?php

declare(strict_types=1);

namespace Omersia\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Omersia\Core\Models\ApiKey;

class ApiKeyGenerateCommand extends Command
{
    protected $signature = 'apikey:generate
        {--name=storefront : Name for the API key}
        {--sync : Sync the key to storefront/.env.local}
        {--force : Regenerate even if key exists}';

    protected $description = 'Generate a new API key for frontend/external access';

    public function handle(): int
    {
        $name = $this->option('name');
        $force = $this->option('force');
        $sync = $this->option('sync');

        try {
            // 1. Check if API key with this name already exists
            $existingKey = ApiKey::where('name', $name)->first();

            if ($existingKey && ! $force) {
                $this->error("API key '{$name}' already exists. Use --force to regenerate.");

                return Command::FAILURE;
            }

            // 2. Generate the plain key (will be shown only once)
            $plainKey = Str::random(64);

            if ($existingKey) {
                // Regenerate existing key
                $this->warn("Regenerating API key '{$name}'...");
                $hashedKey = hash('sha256', $plainKey);
                $existingKey->update(['key' => $hashedKey, 'active' => true]);
                $apiKey = $existingKey;
            } else {
                // Create new key
                $this->info("Generating new API key '{$name}'...");
                $hashedKey = hash('sha256', $plainKey);
                $apiKey = ApiKey::create([
                    'name' => $name,
                    'key' => $hashedKey,
                    'active' => true,
                ]);
            }

            // 3. Update backend/.env file
            $this->updateBackendEnv($plainKey);

            // 4. Sync to storefront/.env.local if requested
            if ($sync) {
                $this->syncToStorefront($plainKey);
            }

            // 5. Display success message
            $this->newLine();
            $this->info('✓ API key generated successfully!');
            $this->newLine();
            $this->line('─────────────────────────────────────────────────────────────────');
            $this->line('API Key Details:');
            $this->line('─────────────────────────────────────────────────────────────────');
            $this->line("Name: {$apiKey->name}");
            $this->line("Key:  {$plainKey}");
            $this->line('─────────────────────────────────────────────────────────────────');
            $this->newLine();
            $this->warn('⚠️  IMPORTANT: Save this key securely. You won\'t be able to see it again!');
            $this->newLine();
            $this->info('The key has been stored (hashed) in the database.');
            $this->info('The plain key has been added to backend/.env as FRONT_API_KEY');

            if ($sync) {
                $this->info('The plain key has been synced to storefront/.env.local as API_KEY');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate API key: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function updateBackendEnv(string $plainKey): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->warn('No .env file found in backend. Creating from .env.example...');
            $examplePath = base_path('.env.example');
            if (File::exists($examplePath)) {
                File::copy($examplePath, $envPath);
            } else {
                throw new \RuntimeException('No .env or .env.example file found');
            }
        }

        $envContent = File::get($envPath);

        // Check if FRONT_API_KEY exists
        if (preg_match('/^FRONT_API_KEY=.*$/m', $envContent)) {
            // Replace existing key
            $envContent = preg_replace(
                '/^FRONT_API_KEY=.*$/m',
                "FRONT_API_KEY={$plainKey}",
                $envContent
            );
        } else {
            // Append new key
            $envContent = rtrim($envContent)."\n\nFRONT_API_KEY={$plainKey}\n";
        }

        File::put($envPath, $envContent);
    }

    protected function syncToStorefront(string $plainKey): void
    {
        // Determine storefront path (production or development)
        $storefrontPath = is_dir('/var/www/storefront')
            ? '/var/www/storefront'
            : base_path('../storefront');

        if (! is_dir($storefrontPath)) {
            $this->warn('Storefront directory not found. Skipping sync.');

            return;
        }

        $envLocalPath = $storefrontPath.'/.env.local';

        // Create or update .env.local
        if (File::exists($envLocalPath)) {
            $envContent = File::get($envLocalPath);

            // Check if FRONT_API_KEY exists (used by Next.js)
            if (preg_match('/^FRONT_API_KEY=.*$/m', $envContent)) {
                $envContent = preg_replace(
                    '/^FRONT_API_KEY=.*$/m',
                    "FRONT_API_KEY={$plainKey}",
                    $envContent
                );
            } else {
                $envContent = rtrim($envContent)."\nFRONT_API_KEY={$plainKey}\n";
            }

            File::put($envLocalPath, $envContent);
        } else {
            // Create new .env.local file
            $envContent = "# Omersia Storefront Configuration\nFRONT_API_KEY={$plainKey}\n";
            File::put($envLocalPath, $envContent);
        }

        $this->info("✓ Synced API key to {$envLocalPath}");

        // Also update root .env for docker-compose
        $this->syncToRootEnv($plainKey);
    }

    protected function syncToRootEnv(string $plainKey): void
    {
        // Determine root path - in Docker the storefront is mounted at /var/www/storefront
        // which maps to the root/storefront folder, so root .env is at /var/www/storefront/../.env
        if (is_dir('/var/www/storefront')) {
            // Docker environment
            $rootEnvPath = '/var/www/storefront/../.env';
        } else {
            // Local environment
            $rootEnvPath = base_path('../.env');
        }

        if (File::exists($rootEnvPath)) {
            $envContent = File::get($rootEnvPath);

            if (preg_match('/^FRONT_API_KEY=.*$/m', $envContent)) {
                $envContent = preg_replace(
                    '/^FRONT_API_KEY=.*$/m',
                    "FRONT_API_KEY={$plainKey}",
                    $envContent
                );
            } else {
                $envContent = rtrim($envContent)."\nFRONT_API_KEY={$plainKey}\n";
            }

            File::put($rootEnvPath, $envContent);
        } else {
            // Create new root .env file
            $envContent = "# Omersia Docker Environment\nFRONT_API_KEY={$plainKey}\n";
            File::put($rootEnvPath, $envContent);
        }

        $this->info('✓ Synced API key to root .env for docker-compose');
    }
}
