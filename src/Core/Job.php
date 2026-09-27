<?php

declare(strict_types=1);

namespace Niang\Core;

abstract class Job
{
    /** Nombre de tentatives avant que Queue ne déplace ce job vers les jobs échoués. */
    public int $tries = 1;

    /** Requête qui a mis ce job en file (renseigné par Queue) : repris dans les logs du worker. */
    public ?string $requestId = null;

    /** Locataire courant quand le job a été mis en file (renseigné par Queue) : rétabli par le worker. */
    public int|string|null $tenantId = null;

    abstract public function handle(): void;
}
