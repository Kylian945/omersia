<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\Gdpr\GdprServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdprServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(GdprServiceProvider::class));
        $this->assertTrue(is_subclass_of(GdprServiceProvider::class, ServiceProvider::class));
    }
}
