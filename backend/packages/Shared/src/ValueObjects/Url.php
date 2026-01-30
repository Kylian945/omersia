<?php

declare(strict_types=1);

namespace Omersia\Shared\ValueObjects;

use InvalidArgumentException;

final class Url extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    private function validate(string $value): void
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL: {$value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function scheme(): ?string
    {
        return parse_url($this->value, PHP_URL_SCHEME);
    }

    public function host(): ?string
    {
        return parse_url($this->value, PHP_URL_HOST);
    }

    public function path(): ?string
    {
        return parse_url($this->value, PHP_URL_PATH);
    }

    public function query(): ?string
    {
        return parse_url($this->value, PHP_URL_QUERY);
    }

    public function isSecure(): bool
    {
        return $this->scheme() === 'https';
    }
}
