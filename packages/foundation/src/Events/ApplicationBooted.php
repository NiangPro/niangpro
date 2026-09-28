<?php

declare(strict_types=1);

namespace Niang\Core\Events;

/** Événement du framework (roadmap §49) : Après le démarrage de tous les Service Providers (register() puis boot()). */
final class ApplicationBooted
{
    public function __construct(public readonly \Niang\Core\Application $app)
    {
    }
}
