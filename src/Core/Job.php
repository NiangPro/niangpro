<?php

namespace Niang\Core;

abstract class Job
{
    /** Nombre de tentatives avant que Queue ne déplace ce job vers les jobs échoués. */
    public int $tries = 1;

    abstract public function handle(): void;
}
