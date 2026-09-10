<?php

namespace Niang\Core;

abstract class ServiceProvider
{
    public function __construct(protected Application $app)
    {
    }

    /** Enregistrez ici des bindings dans le Container. Tous les register() tournent avant tout boot(). */
    public function register(): void
    {
    }

    /** Écouteurs d'événements, initialisation... exécuté après que tous les providers sont enregistrés. */
    public function boot(): void
    {
    }
}
