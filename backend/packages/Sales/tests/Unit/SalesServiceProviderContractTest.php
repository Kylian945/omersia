<?php

declare(strict_types=1);

namespace Omersia\Sales\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\Sales\SalesServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SalesServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(SalesServiceProvider::class));
        $this->assertTrue(is_subclass_of(SalesServiceProvider::class, ServiceProvider::class));
    }
}
