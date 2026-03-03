<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\Api\ApiServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(ApiServiceProvider::class));
        $this->assertTrue(is_subclass_of(ApiServiceProvider::class, ServiceProvider::class));
    }
}
