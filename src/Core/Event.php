<?php

namespace Niang\Core;

use Niang\Core\Contracts\ShouldQueue;
use Niang\Core\Jobs\CallQueuedListener;

class Event
{
    /** @var array<string, array<int, \Closure|class-string>> */
    private static array $listeners = [];

    /**
     * $listener est une closure (toujours synchrone) ou le nom d'une classe exposant une méthode
     * handle(...) — une classe qui implémente Contracts\ShouldQueue est différée sur Queue plutôt
     * qu'exécutée immédiatement (voir dispatch()).
     */
    public static function listen(string $event, \Closure|string $listener): void
    {
        self::$listeners[$event][] = $listener;
    }

    public static function dispatch(string $event, mixed ...$payload): void
    {
        foreach (self::$listeners[$event] ?? [] as $listener) {
            if (is_string($listener) && is_a($listener, ShouldQueue::class, true)) {
                Queue::push(new CallQueuedListener($listener, $payload));
                continue;
            }

            if (is_string($listener)) {
                (new $listener())->handle(...$payload);
                continue;
            }

            $listener(...$payload);
        }
    }

    /** @internal remet Event dans son état initial — appelé par TestCase entre deux tests. */
    public static function reset(): void
    {
        self::$listeners = [];
    }
}
