<?php

declare(strict_types=1);

namespace Niang\Core\Events;

/** Événement du framework (roadmap §49) : Route trouvée, paramètres renseignés, avant les middlewares et l'action. */
final class RouteMatched
{
    /** @param array<string, mixed> $route la route (méthode, uri, action, middleware, name...) */
    public function __construct(public readonly \Niang\Core\Http\Request $request, public readonly array $route)
    {
    }
}
