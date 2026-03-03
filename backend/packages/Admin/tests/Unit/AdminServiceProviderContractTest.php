<?php

declare(strict_types=1);

namespace Omersia\Admin\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\Admin\AdminServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(AdminServiceProvider::class));
        $this->assertTrue(is_subclass_of(AdminServiceProvider::class, ServiceProvider::class));
    }
}
