<?php

declare(strict_types=1);

namespace Niang\Core\Database;

abstract class Seeder
{
    abstract public function run(): void;
}
