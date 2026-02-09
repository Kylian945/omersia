<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * SECURITY: This method includes a critical safeguard to prevent tests
     * from running on the production/development database. Tests MUST use
     * SQLite in-memory (:memory:) to avoid data loss.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // SECURITY: Always check database configuration when running tests
        // This check runs regardless of APP_ENV to prevent accidental data loss
        $this->ensureSafeTestingDatabase($app);

        return $app;
    }

    /**
     * Ensure tests are running on a safe database (SQLite in-memory only)
     *
     * @throws RuntimeException if tests would run on a non-safe database
     */
    private function ensureSafeTestingDatabase(Application $app): void
    {
        $defaultConnection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$defaultConnection}.database");

        $isSafeTestingDatabase = $defaultConnection === 'sqlite' && $database === ':memory:';

        if (! $isSafeTestingDatabase) {
            throw new RuntimeException(
                sprintf(
                    "CRITICAL: Unsafe test database configuration detected!\n".
                    "  Connection: %s\n".
                    "  Database: %s\n\n".
                    "Tests MUST use SQLite in-memory to protect your data.\n".
                    "Check that phpunit.xml or .env.testing sets:\n".
                    "  DB_CONNECTION=sqlite\n".
                    "  DB_DATABASE=:memory:\n\n".
                    "Refusing to run tests.",
                    $defaultConnection,
                    $database === '' ? '[empty]' : $database
                )
            );
        }
    }
}
