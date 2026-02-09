<?php

declare(strict_types=1);

namespace Omersia\Shared\ValueObjects;

use InvalidArgumentException;

final class Quantity extends ValueObject
{
    private int $value;

    public function __construct(int $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    private function validate(int $value): void
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function add(Quantity $other): self
    {
        return new self($this->value + $other->value);
    }

    public function subtract(Quantity $other): self
    {
        return new self($this->value - $other->value);
    }

    public function multiply(int $multiplier): self
    {
        return new self($this->value * $multiplier);
    }

    public function isGreaterThan(Quantity $other): bool
    {
        return $this->value > $other->value;
    }

    public function isLessThan(Quantity $other): bool
    {
        return $this->value < $other->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === 0;
    }
}
