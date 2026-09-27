<?php

declare(strict_types=1);

namespace Niang\Core;

use Niang\Core\Contracts\ShouldQueue;
use Niang\Core\Jobs\CallQueuedListener;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Deux formes d'événements :
 *
 *  - typés (recommandé) : un objet, écouté par le nom de sa classe — ou d'une classe parente, ou d'une
 *    interface qu'il implémente —, que les écouteurs reçoivent en argument :
 *      Event::listen(UserRegistered::class, SendWelcomeEmail::class);
 *      Event::dispatch(new UserRegistered($user));
 *  - nommés (historique, toujours pris en charge) : une chaîne et des arguments libres :
 *      Event::listen('user.registered', fn (array $user) => ...);
 *      Event::dispatch('user.registered', $user);
 *
 * Un événement qui implémente StoppableEventInterface (voir Events\StoppableEvent) arrête la
 * propagation dès qu'un écouteur l'a demandé. Pour l'injection PSR-14, voir Events\Dispatcher.
 */
class Event
{
    /** @var array<string, list<\Closure|string>> closure ou nom de classe exposant handle() */
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

    /**
     * @template T of object
     * @param T|string $event
     * @return T|null l'objet événement (éventuellement modifié par les écouteurs), null pour un événement nommé
     */
    public static function dispatch(object|string $event, mixed ...$payload): ?object
    {
        if (is_string($event)) {
            foreach (self::$listeners[$event] ?? [] as $listener) {
                self::call($listener, array_values($payload));
            }

            return null;
        }

        foreach (self::listenersFor($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            self::call($listener, [$event]);
        }

        return $event;
    }

    /** Vrai si au moins un écouteur est enregistré pour ce nom ou cette classe. */
    public static function hasListeners(string $event): bool
    {
        return !empty(self::$listeners[$event]);
    }

    /** @internal remet Event dans son état initial — appelé par TestCase entre deux tests. */
    public static function reset(): void
    {
        self::$listeners = [];
    }

    /**
     * Écouteurs de la classe exacte d'abord, puis de ses classes parentes, puis de ses interfaces.
     *
     * @return list<\Closure|string>
     */
    public static function listenersFor(object $event): array
    {
        $types = [$event::class, ...array_values(class_parents($event) ?: []), ...array_values(class_implements($event) ?: [])];
        $listeners = [];

        foreach ($types as $type) {
            array_push($listeners, ...(self::$listeners[$type] ?? []));
        }

        return $listeners;
    }

    /**
     * @internal écouteur enregistré par son nom de classe : l'instancie et renvoie son handle().
     * Une classe inexistante ou sans handle() lève une erreur qui la nomme.
     */
    public static function classListener(string $class): \Closure
    {
        $listener = class_exists($class) ? new $class() : null;

        if ($listener === null || !method_exists($listener, 'handle')) {
            throw new \InvalidArgumentException("Écouteur « $class » : classe introuvable ou sans méthode handle().");
        }

        return \Closure::fromCallable([$listener, 'handle']);
    }

    /** @param list<mixed> $payload */
    private static function call(\Closure|string $listener, array $payload): void
    {
        if (is_string($listener) && is_a($listener, ShouldQueue::class, true)) {
            Queue::push(new CallQueuedListener($listener, $payload));
            return;
        }

        if (is_string($listener)) {
            self::classListener($listener)(...$payload);
            return;
        }

        $listener(...$payload);
    }
}
