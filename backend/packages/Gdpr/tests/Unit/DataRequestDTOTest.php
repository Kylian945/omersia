<?php

declare(strict_types=1);

namespace Omersia\Gdpr\Tests\Unit;

use Omersia\Gdpr\DTO\DataRequestDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataRequestDTOTest extends TestCase
{
    #[Test]
    public function it_constructs_with_explicit_values(): void
    {
        $dto = new DataRequestDTO(
            customerId: 5,
            type: 'export',
            reason: 'I want a copy of my data.',
        );

        $this->assertEquals(5, $dto->customerId);
        $this->assertEquals('export', $dto->type);
        $this->assertEquals('I want a copy of my data.', $dto->reason);
    }

    #[Test]
    public function it_defaults_reason_to_null(): void
    {
        $dto = new DataRequestDTO(
            customerId: 1,
            type: 'access',
        );

        $this->assertNull($dto->reason);
    }

    #[Test]
    public function from_array_maps_type_correctly(): void
    {
        foreach (['access', 'export', 'deletion', 'rectification'] as $type) {
            $dto = DataRequestDTO::fromArray(['type' => $type], 1);
            $this->assertEquals($type, $dto->type);
        }
    }

    #[Test]
    public function from_array_maps_reason_when_provided(): void
    {
        $dto = DataRequestDTO::fromArray([
            'type' => 'deletion',
            'reason' => 'I no longer wish to use this service.',
        ], 10);

        $this->assertEquals('I no longer wish to use this service.', $dto->reason);
    }

    #[Test]
    public function from_array_defaults_reason_to_null_when_absent(): void
    {
        $dto = DataRequestDTO::fromArray(['type' => 'access'], 3);

        $this->assertNull($dto->reason);
    }

    #[Test]
    public function from_array_sets_customer_id(): void
    {
        $dto = DataRequestDTO::fromArray(['type' => 'access'], 42);

        $this->assertEquals(42, $dto->customerId);
    }

    #[Test]
    public function to_array_contains_all_required_keys(): void
    {
        $dto = new DataRequestDTO(customerId: 1, type: 'access', reason: null);

        $array = $dto->toArray();

        $this->assertArrayHasKey('customer_id', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertArrayHasKey('requested_at', $array);
    }

    #[Test]
    public function to_array_sets_status_to_pending(): void
    {
        $dto = new DataRequestDTO(customerId: 1, type: 'export');

        $array = $dto->toArray();

        $this->assertEquals('pending', $array['status']);
    }

    #[Test]
    public function to_array_sets_requested_at_to_now(): void
    {
        $dto = new DataRequestDTO(customerId: 1, type: 'deletion');

        $before = now()->subSecond();
        $array = $dto->toArray();
        $after = now()->addSecond();

        $this->assertGreaterThanOrEqual($before->timestamp, $array['requested_at']->timestamp);
        $this->assertLessThanOrEqual($after->timestamp, $array['requested_at']->timestamp);
    }

    #[Test]
    public function to_array_maps_all_values_correctly(): void
    {
        $dto = new DataRequestDTO(
            customerId: 7,
            type: 'rectification',
            reason: 'My name is spelled wrong.',
        );

        $array = $dto->toArray();

        $this->assertEquals(7, $array['customer_id']);
        $this->assertEquals('rectification', $array['type']);
        $this->assertEquals('pending', $array['status']);
        $this->assertEquals('My name is spelled wrong.', $array['reason']);
    }

    #[Test]
    public function to_array_preserves_null_reason(): void
    {
        $dto = new DataRequestDTO(customerId: 1, type: 'access', reason: null);

        $array = $dto->toArray();

        $this->assertNull($array['reason']);
    }
}
