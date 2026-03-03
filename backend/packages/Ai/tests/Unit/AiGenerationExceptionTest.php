<?php

declare(strict_types=1);

namespace Omersia\Ai\Tests\Unit;

use Omersia\Ai\Exceptions\AiGenerationException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class AiGenerationExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_runtime_exception(): void
    {
        $exception = new AiGenerationException('Test error');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    #[Test]
    public function it_stores_the_message(): void
    {
        $exception = new AiGenerationException('Something went wrong with AI generation.');

        $this->assertEquals('Something went wrong with AI generation.', $exception->getMessage());
    }

    #[Test]
    public function it_stores_a_custom_code(): void
    {
        $exception = new AiGenerationException('Error', 42);

        $this->assertEquals(42, $exception->getCode());
    }

    #[Test]
    public function it_stores_a_previous_exception(): void
    {
        $previous = new \RuntimeException('Root cause');
        $exception = new AiGenerationException('Wrapped error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function it_can_be_thrown_and_caught(): void
    {
        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Provider failed.');

        throw new AiGenerationException('Provider failed.');
    }

    #[Test]
    public function it_can_be_caught_as_runtime_exception(): void
    {
        $caught = null;

        try {
            throw new AiGenerationException('Catchable as RuntimeException');
        } catch (RuntimeException $e) {
            $caught = $e;
        }

        $this->assertInstanceOf(AiGenerationException::class, $caught);
        $this->assertEquals('Catchable as RuntimeException', $caught->getMessage());
    }
}
