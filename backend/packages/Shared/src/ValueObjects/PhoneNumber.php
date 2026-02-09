<?php

declare(strict_types=1);

namespace Omersia\Shared\ValueObjects;

use InvalidArgumentException;

final class PhoneNumber extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = $this->normalize($value);
        $this->validate($normalized);
        $this->value = $normalized;
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[^+\d]/', '', $value);
    }

    private function validate(string $value): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Phone number cannot be empty');
        }

        if (! preg_match('/^\+?\d{8,15}$/', $value)) {
            throw new InvalidArgumentException("Invalid phone number format: {$value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function format(): string
    {
        if (str_starts_with($this->value, '+33')) {
            $number = substr($this->value, 3);

            return '+33 '.implode(' ', str_split($number, 2));
        }

        return $this->value;
    }
}
