<?php

namespace Niang\Core\Events;

use Niang\Core\Event;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * PSR-14 : à injecter (`EventDispatcherInterface $events`) dans du code qui ne doit pas dépendre de
 * la façade statique, ou dans une bibliothèque tierce compatible. Mêmes écouteurs que Event::listen().
 */
final class Dispatcher implements EventDispatcherInterface, ListenerProviderInterface
{
    public function dispatch(object $event): object
    {
        return Event::dispatch($event) ?? $event;
    }

    /** @return iterable<callable> */
    public function getListenersForEvent(object $event): iterable
    {
        foreach (Event::listenersFor($event) as $listener) {
            yield is_string($listener) ? fn (object $e) => (new $listener())->handle($e) : $listener;
        }
    }
}
