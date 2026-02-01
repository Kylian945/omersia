<?php

declare(strict_types=1);

namespace Omersia\Shared\Tests\Unit\ValueObjects;

use InvalidArgumentException;
use Omersia\Shared\ValueObjects\Email;
use Tests\TestCase;

class EmailTest extends TestCase
{
    /** @test */
    public function it_creates_valid_email(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', $email->value());
    }

    /** @test */
    public function it_normalizes_email_to_lowercase(): void
    {
        $email = new Email('Test@EXAMPLE.COM');

        $this->assertEquals('test@example.com', $email->value());
    }

    /** @test */
    public function it_trims_whitespace(): void
    {
        $email = new Email('  test@example.com  ');

        $this->assertEquals('test@example.com', $email->value());
    }

    /** @test */
    public function it_throws_exception_for_invalid_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address');

        new Email('invalid-email');
    }

    /** @test */
    public function it_throws_exception_for_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('');
    }

    /** @test */
    public function it_throws_exception_for_email_without_at_symbol(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('testexample.com');
    }

    /** @test */
    public function it_extracts_domain(): void
    {
        $email = new Email('user@example.com');

        $this->assertEquals('example.com', $email->domain());
    }

    /** @test */
    public function it_extracts_local_part(): void
    {
        $email = new Email('user@example.com');

        $this->assertEquals('user', $email->localPart());
    }

    /** @test */
    public function it_handles_complex_email_addresses(): void
    {
        $email = new Email('user+tag@sub.example.com');

        $this->assertEquals('user+tag@sub.example.com', $email->value());
        $this->assertEquals('sub.example.com', $email->domain());
        $this->assertEquals('user+tag', $email->localPart());
    }
}
