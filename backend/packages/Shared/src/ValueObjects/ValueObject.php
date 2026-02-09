<?php

declare(strict_types=1);

namespace Omersia\Shared\ValueObjects;

use JsonSerializable;

abstract class ValueObject implements JsonSerializable
{
    abstract public function value(): mixed;

    public function equals(ValueObject $other): bool
    {
        return get_class($this) === get_class($other)
            && $this->value() === $other->value();
    }

    public function __toString(): string
    {
        return (string) $this->value();
    }

    public function jsonSerialize(): mixed
    {
        return $this->value();
    }
}
