<?php

declare(strict_types=1);

namespace Niang\Core;

abstract class Job
{
    /** Nombre de tentatives avant que Queue ne déplace ce job vers les jobs échoués. */
    public int $tries = 1;

    /**
     * Contexte capturé à la mise en file (Queue::stampUsing()) et rétabli par le worker : 'log' est
     * ajouté aux logs du job (ex. request_id de la requête d'origine), 'tenant' désigne son locataire.
     *
     * @var array<string, mixed>
     */
    public array $context = [];

    abstract public function handle(): void;
}
