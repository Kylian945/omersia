<?php

declare(strict_types=1);

namespace Omersia\Shared\Tests\Unit\ValueObjects;

use InvalidArgumentException;
use Omersia\Shared\ValueObjects\PhoneNumber;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    /** @test */
    public function it_creates_valid_phone_number(): void
    {
        $phone = new PhoneNumber('+33612345678');

        $this->assertEquals('+33612345678', $phone->value());
    }

    /** @test */
    public function it_normalizes_phone_number_by_removing_spaces(): void
    {
        $phone = new PhoneNumber('+33 6 12 34 56 78');

        $this->assertEquals('+33612345678', $phone->value());
    }

    /** @test */
    public function it_normalizes_phone_number_by_removing_dashes(): void
    {
        $phone = new PhoneNumber('+33-6-12-34-56-78');

        $this->assertEquals('+33612345678', $phone->value());
    }

    /** @test */
    public function it_normalizes_phone_number_by_removing_parentheses(): void
    {
        $phone = new PhoneNumber('+33 (6) 12 34 56 78');

        $this->assertEquals('+33612345678', $phone->value());
    }

    /** @test */
    public function it_accepts_phone_number_without_plus(): void
    {
        $phone = new PhoneNumber('33612345678');

        $this->assertEquals('33612345678', $phone->value());
    }

    /** @test */
    public function it_throws_exception_for_empty_phone_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number cannot be empty');

        new PhoneNumber('');
    }

    /** @test */
    public function it_throws_exception_for_too_short_phone_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number format');

        new PhoneNumber('1234567');
    }

    /** @test */
    public function it_throws_exception_for_too_long_phone_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number format');

        new PhoneNumber('1234567890123456');
    }

    /** @test */
    public function it_throws_exception_for_phone_number_with_letters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PhoneNumber('+33 ABC DEF GHI');
    }

    /** @test */
    public function it_accepts_minimum_length_phone_number(): void
    {
        $phone = new PhoneNumber('12345678');

        $this->assertEquals('12345678', $phone->value());
    }

    /** @test */
    public function it_accepts_maximum_length_phone_number(): void
    {
        $phone = new PhoneNumber('+123456789012345');

        $this->assertEquals('+123456789012345', $phone->value());
    }

    /** @test */
    public function it_formats_french_phone_number(): void
    {
        $phone = new PhoneNumber('+33612345678');

        $this->assertEquals('+33 61 23 45 67 8', $phone->format());
    }

    /** @test */
    public function it_returns_unformatted_for_non_french_numbers(): void
    {
        $phone = new PhoneNumber('+14155552671');

        $this->assertEquals('+14155552671', $phone->format());
    }

    /** @test */
    public function it_formats_french_number_with_spaces(): void
    {
        $phone = new PhoneNumber('+33 6 12 34 56 78');

        $formatted = $phone->format();

        $this->assertStringStartsWith('+33 ', $formatted);
    }
}
