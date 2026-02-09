<?php

namespace Tests;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutVite();
    }

    protected function withoutVite(): void
    {
        $manifest = json_encode([
            'resources/js/app.js' => ['file' => 'assets/app.js', 'src' => 'resources/js/app.js'],
            'resources/css/app.css' => ['file' => 'assets/app.css', 'src' => 'resources/css/app.css', 'isEntry' => true],
        ]);

        $buildDir = public_path('build');

        if (! is_dir($buildDir)) {
            mkdir($buildDir, 0755, true);
        }

        file_put_contents($buildDir.'/manifest.json', $manifest);
    }

    protected function tearDown(): void
    {
        $manifestPath = public_path('build/manifest.json');

        if (file_exists($manifestPath)) {
            unlink($manifestPath);
            @rmdir(public_path('build'));
        }

        parent::tearDown();
    }
}
