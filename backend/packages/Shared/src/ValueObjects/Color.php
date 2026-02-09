<?php

declare(strict_types=1);

namespace Omersia\Shared\ValueObjects;

use InvalidArgumentException;

final class Color extends ValueObject
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
        $value = strtolower(trim($value));
        if (! str_starts_with($value, '#')) {
            $value = '#'.$value;
        }

        return $value;
    }

    private function validate(string $value): void
    {
        if (! preg_match('/^#[0-9a-f]{6}$/i', $value)) {
            throw new InvalidArgumentException("Invalid hex color format: {$value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function red(): int
    {
        return hexdec(substr($this->value, 1, 2));
    }

    public function green(): int
    {
        return hexdec(substr($this->value, 3, 2));
    }

    public function blue(): int
    {
        return hexdec(substr($this->value, 5, 2));
    }

    public function toRgb(): array
    {
        return [
            'r' => $this->red(),
            'g' => $this->green(),
            'b' => $this->blue(),
        ];
    }

    public function toRgbString(): string
    {
        return sprintf('rgb(%d, %d, %d)', $this->red(), $this->green(), $this->blue());
    }
}
