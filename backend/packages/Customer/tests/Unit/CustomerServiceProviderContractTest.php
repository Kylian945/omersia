<?php

declare(strict_types=1);

namespace Omersia\Customer\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\Customer\CustomerServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(CustomerServiceProvider::class));
        $this->assertTrue(is_subclass_of(CustomerServiceProvider::class, ServiceProvider::class));
    }
}
