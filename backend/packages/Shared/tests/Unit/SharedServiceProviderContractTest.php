<?php

declare(strict_types=1);

namespace Omersia\Shared\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\Shared\SharedServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SharedServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(SharedServiceProvider::class));
        $this->assertTrue(is_subclass_of(SharedServiceProvider::class, ServiceProvider::class));
    }
}
