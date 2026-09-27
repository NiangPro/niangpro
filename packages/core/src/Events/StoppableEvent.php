<?php

declare(strict_types=1);

namespace Niang\Core\Events;

use Psr\EventDispatcher\StoppableEventInterface;

/** Classe de base facultative : un écouteur appelle stopPropagation() pour que les suivants ne reçoivent pas l'événement. */
abstract class StoppableEvent implements StoppableEventInterface
{
    private bool $stopped = false;

    public function stopPropagation(): void
    {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
}
