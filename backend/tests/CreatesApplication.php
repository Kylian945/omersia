<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if ($app['env'] === 'testing') {
            $defaultConnection = (string) $app['config']->get('database.default');
            $database = (string) $app['config']->get("database.connections.{$defaultConnection}.database");

            $isSafeTestingDatabase = $defaultConnection === 'sqlite' && $database === ':memory:';

            if (! $isSafeTestingDatabase) {
                throw new RuntimeException(
                    sprintf(
                        'Unsafe test database configuration detected (connection=%s, database=%s). '.
                        'Refusing to run tests to protect development data.',
                        $defaultConnection,
                        $database === '' ? '[empty]' : $database
                    )
                );
            }
        }

        return $app;
    }
}
