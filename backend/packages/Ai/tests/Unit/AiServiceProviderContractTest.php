<?php

declare(strict_types=1);

namespace Omersia\Ai\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\Ai\AiServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(AiServiceProvider::class));
        $this->assertTrue(is_subclass_of(AiServiceProvider::class, ServiceProvider::class));
    }
}
