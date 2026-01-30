<?php

declare(strict_types=1);

namespace Omersia\Admin\Support;

class Hooks
{
    protected static array $actions = [];

    public static function addAction(string $hook, callable $cb, int $priority = 10)
    {
        static::$actions[$hook][$priority][] = $cb;
    }

    public static function doAction(string $hook, ...$args)
    {
        foreach (collect(static::$actions[$hook] ?? [])->sortKeys() as $cbs) {
            foreach ($cbs as $cb) {
                $cb(...$args);
            }
        }
    }
}
