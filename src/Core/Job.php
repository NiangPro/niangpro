<?php

namespace Niang\Core;

abstract class Job
{
    abstract public function handle(): void;
}
