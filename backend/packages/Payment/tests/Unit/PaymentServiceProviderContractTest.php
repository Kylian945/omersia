<?php

declare(strict_types=1);

namespace Omersia\Payment\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\Payment\PaymentServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(PaymentServiceProvider::class));
        $this->assertTrue(is_subclass_of(PaymentServiceProvider::class, ServiceProvider::class));
    }
}
