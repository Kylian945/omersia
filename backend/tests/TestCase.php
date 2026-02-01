<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function withoutVite(): void
    {
        $this->app->singleton(Vite::class, function () {
            return new class extends Vite {
                public function __invoke($entrypoints, $buildDirectory = null): HtmlString
                {
                    return new HtmlString('');
                }

                public function content($asset, $buildDirectory = null): string
                {
                    return '';
                }

                public function asset($asset, $buildDirectory = null): string
                {
                    return "build/assets/{$asset}";
                }
            };
        });
    }
}
