<?php

declare(strict_types=1);

namespace Omersia\Apparence\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\Apparence\ApparenceServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApparenceServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(ApparenceServiceProvider::class));
        $this->assertTrue(is_subclass_of(ApparenceServiceProvider::class, ServiceProvider::class));
    }
}
