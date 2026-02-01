<?php

declare(strict_types=1);

namespace Omersia\Shared\Tests\Unit\ValueObjects;

use InvalidArgumentException;
use Omersia\Shared\ValueObjects\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    /** @test */
    public function it_creates_money_with_default_currency(): void
    {
        $money = new Money(1000);

        $this->assertEquals(1000, $money->amount());
        $this->assertEquals('EUR', $money->currency());
    }

    /** @test */
    public function it_creates_money_with_custom_currency(): void
    {
        $money = new Money(1000, 'USD');

        $this->assertEquals(1000, $money->amount());
        $this->assertEquals('USD', $money->currency());
    }

    /** @test */
    public function it_normalizes_currency_to_uppercase(): void
    {
        $money = new Money(1000, 'usd');

        $this->assertEquals('USD', $money->currency());
    }

    /** @test */
    public function it_throws_exception_for_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        new Money(-100);
    }

    /** @test */
    public function it_throws_exception_for_invalid_currency_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid currency code');

        new Money(1000, 'US');
    }

    /** @test */
    public function it_accepts_zero_amount(): void
    {
        $money = new Money(0);

        $this->assertEquals(0, $money->amount());
    }

    /** @test */
    public function it_converts_amount_to_units(): void
    {
        $money = new Money(1050);

        $this->assertEquals(10.50, $money->amountInUnits());
    }

    /** @test */
    public function it_adds_money_with_same_currency(): void
    {
        $money1 = new Money(1000, 'EUR');
        $money2 = new Money(500, 'EUR');

        $result = $money1->add($money2);

        $this->assertEquals(1500, $result->amount());
        $this->assertEquals('EUR', $result->currency());
    }

    /** @test */
    public function it_throws_exception_when_adding_different_currencies(): void
    {
        $money1 = new Money(1000, 'EUR');
        $money2 = new Money(500, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot operate on different currencies');

        $money1->add($money2);
    }

    /** @test */
    public function it_subtracts_money_with_same_currency(): void
    {
        $money1 = new Money(1000, 'EUR');
        $money2 = new Money(300, 'EUR');

        $result = $money1->subtract($money2);

        $this->assertEquals(700, $result->amount());
    }

    /** @test */
    public function it_throws_exception_when_subtracting_different_currencies(): void
    {
        $money1 = new Money(1000, 'EUR');
        $money2 = new Money(500, 'USD');

        $this->expectException(InvalidArgumentException::class);

        $money1->subtract($money2);
    }

    /** @test */
    public function it_multiplies_money(): void
    {
        $money = new Money(1000, 'EUR');

        $result = $money->multiply(2.5);

        $this->assertEquals(2500, $result->amount());
        $this->assertEquals('EUR', $result->currency());
    }

    /** @test */
    public function it_rounds_multiplication_result(): void
    {
        $money = new Money(1000, 'EUR');

        $result = $money->multiply(1.567);

        $this->assertEquals(1567, $result->amount());
    }

    /** @test */
    public function it_compares_greater_than(): void
    {
        $money1 = new Money(1000, 'EUR');
        $money2 = new Money(500, 'EUR');

        $this->assertTrue($money1->isGreaterThan($money2));
        $this->assertFalse($money2->isGreaterThan($money1));
    }

    /** @test */
    public function it_compares_less_than(): void
    {
        $money1 = new Money(500, 'EUR');
        $money2 = new Money(1000, 'EUR');

        $this->assertTrue($money1->isLessThan($money2));
        $this->assertFalse($money2->isLessThan($money1));
    }

    /** @test */
    public function it_throws_exception_when_comparing_different_currencies(): void
    {
        $money1 = new Money(1000, 'EUR');
        $money2 = new Money(500, 'USD');

        $this->expectException(InvalidArgumentException::class);

        $money1->isGreaterThan($money2);
    }

    /** @test */
    public function it_formats_money_in_euros(): void
    {
        $money = new Money(1050, 'EUR');

        $this->assertEquals('10,50 €', $money->format());
    }

    /** @test */
    public function it_formats_money_in_dollars(): void
    {
        $money = new Money(2599, 'USD');

        $this->assertEquals('25,99 $', $money->format());
    }

    /** @test */
    public function it_formats_money_in_pounds(): void
    {
        $money = new Money(1999, 'GBP');

        $this->assertEquals('19,99 £', $money->format());
    }

    /** @test */
    public function it_formats_money_with_unknown_currency(): void
    {
        $money = new Money(1000, 'JPY');

        $formatted = $money->format();

        $this->assertStringContainsString('10,00', $formatted);
        $this->assertStringContainsString('JPY', $formatted);
    }

    /** @test */
    public function it_returns_value_as_array(): void
    {
        $money = new Money(1000, 'EUR');

        $value = $money->value();

        $this->assertEquals(['amount' => 1000, 'currency' => 'EUR'], $value);
    }

    /** @test */
    public function it_preserves_immutability_on_operations(): void
    {
        $original = new Money(1000, 'EUR');
        $added = $original->add(new Money(500, 'EUR'));

        $this->assertEquals(1000, $original->amount());
        $this->assertEquals(1500, $added->amount());
        $this->assertNotSame($original, $added);
    }
}
