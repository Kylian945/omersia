<?php

declare(strict_types=1);

namespace Omersia\Ai\Tests\Unit;

use Omersia\Ai\Services\BackofficeAssistantService;
use Omersia\Ai\Services\ContentGenerationService;
use Omersia\Ai\Services\ProductImageGenerationService;
use Omersia\Ai\Services\ProductSeoGenerationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_content_generation_service_as_singleton(): void
    {
        $instance1 = $this->app->make(ContentGenerationService::class);
        $instance2 = $this->app->make(ContentGenerationService::class);

        $this->assertInstanceOf(ContentGenerationService::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_registers_product_seo_generation_service_as_singleton(): void
    {
        $instance1 = $this->app->make(ProductSeoGenerationService::class);
        $instance2 = $this->app->make(ProductSeoGenerationService::class);

        $this->assertInstanceOf(ProductSeoGenerationService::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_registers_product_image_generation_service_as_singleton(): void
    {
        $instance1 = $this->app->make(ProductImageGenerationService::class);
        $instance2 = $this->app->make(ProductImageGenerationService::class);

        $this->assertInstanceOf(ProductImageGenerationService::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_registers_backoffice_assistant_service_as_singleton(): void
    {
        $instance1 = $this->app->make(BackofficeAssistantService::class);
        $instance2 = $this->app->make(BackofficeAssistantService::class);

        $this->assertInstanceOf(BackofficeAssistantService::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_resolves_all_four_services_from_the_container(): void
    {
        $this->assertInstanceOf(ContentGenerationService::class, app(ContentGenerationService::class));
        $this->assertInstanceOf(ProductSeoGenerationService::class, app(ProductSeoGenerationService::class));
        $this->assertInstanceOf(ProductImageGenerationService::class, app(ProductImageGenerationService::class));
        $this->assertInstanceOf(BackofficeAssistantService::class, app(BackofficeAssistantService::class));
    }
}
