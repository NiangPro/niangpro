<?php

namespace Niang\Core;

class Event
{
    private static array $listeners = [];

    public static function listen(string $event, \Closure $listener): void
    {
        self::$listeners[$event][] = $listener;
    }

    public static function dispatch(string $event, mixed ...$payload): void
    {
        foreach (self::$listeners[$event] ?? [] as $listener) {
            $listener(...$payload);
        }
    }
}
