<?php

declare(strict_types=1);

namespace Omersia\CMS\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Omersia\CMS\CMSServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CMSServiceProviderContractTest extends TestCase
{
    #[Test]
    public function it_exposes_a_valid_laravel_service_provider_class(): void
    {
        $this->assertTrue(class_exists(CMSServiceProvider::class));
        $this->assertTrue(is_subclass_of(CMSServiceProvider::class, ServiceProvider::class));
    }
}
