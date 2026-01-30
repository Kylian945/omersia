<?php

declare(strict_types=1);

namespace Omersia\Admin\Support;

class AdminMenu
{
    protected static array $items = [];

    public static function add(array $item): void
    {
        static::$items[] = $item;
    }

    public static function all(): array
    {
        return static::$items;
    }
}
