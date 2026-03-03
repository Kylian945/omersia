<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\Catalog\CatalogServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(CatalogServiceProvider::class));
        $this->assertTrue(is_subclass_of(CatalogServiceProvider::class, ServiceProvider::class));
    }
}
