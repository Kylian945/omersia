<?php

declare(strict_types=1);

namespace Omersia\Payment\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptPaymentSecretsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:encrypt-secrets
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt existing payment provider secrets in database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will re-encrypt all payment provider configurations. Continue?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->info('Starting encryption of payment provider secrets...');

        try {
            // Lire directement depuis la table sans passer par le modèle
            $providers = DB::table('payment_providers')->get();

            if ($providers->isEmpty()) {
                $this->info('No payment providers found.');

                return self::SUCCESS;
            }

            $count = 0;
            $skipped = 0;
            $alreadyEncrypted = 0;

            foreach ($providers as $provider) {
                // Check if already encrypted (starts with 'eyJpdiI6', Laravel encrypted format)
                if (str_starts_with($provider->config, 'eyJpdiI6')) {
                    $alreadyEncrypted++;
                    $this->info("Provider #{$provider->id} ({$provider->name}) is already encrypted.");

                    continue;
                }

                $config = json_decode($provider->config, true);

                if (! is_array($config)) {
                    $this->warn("Skipping provider #{$provider->id} - invalid config format");
                    $skipped++;

                    continue;
                }

                // Encrypt manually like Laravel does with encrypted:array cast
                DB::beginTransaction();
                try {
                    $encrypted = Crypt::encryptString(json_encode($config));

                    // Update directly in database without going through model
                    DB::table('payment_providers')
                        ->where('id', $provider->id)
                        ->update([
                            'config' => $encrypted,
                            'updated_at' => now(),
                        ]);

                    $count++;
                    $this->info("Encrypted provider #{$provider->id} ({$provider->name})");
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->warn("Failed to encrypt provider #{$provider->id}: {$e->getMessage()}");
                    $skipped++;
                }
            }

            $this->newLine();
            $this->info("✅ Successfully encrypted: {$count} provider(s)");
            if ($alreadyEncrypted > 0) {
                $this->info("ℹ️  Already encrypted: {$alreadyEncrypted} provider(s)");
            }
            if ($skipped > 0) {
                $this->warn("⚠️  Skipped: {$skipped} provider(s)");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error encrypting secrets: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }
}
