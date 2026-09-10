<?php

namespace Niang\Core;

class Gate
{
    private static array $abilities = [];

    public static function define(string $ability, \Closure $callback): void
    {
        self::$abilities[$ability] = $callback;
    }

    public static function allows(string $ability, mixed ...$args): bool
    {
        if (!isset(self::$abilities[$ability])) {
            return false;
        }

        return (bool) (self::$abilities[$ability])(Auth::user(), ...$args);
    }

    public static function denies(string $ability, mixed ...$args): bool
    {
        return !self::allows($ability, ...$args);
    }
}
