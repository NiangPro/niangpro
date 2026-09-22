<?php

namespace Niang\Core\Jobs;

use Niang\Core\Job;

/**
 * Enrobe un listener ShouldQueue pour le passer sur Queue — voir Event::dispatch(). $listener
 * doit exposer une méthode handle(...) acceptant le même payload que celui de l'événement.
 */
class CallQueuedListener extends Job
{
    public function __construct(private string $listener, private array $payload)
    {
    }

    public function handle(): void
    {
        (new $this->listener())->handle(...$this->payload);
    }
}
