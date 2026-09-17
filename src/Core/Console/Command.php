<?php

namespace Niang\Core\Console;

/**
 * Une commande custom (app/Console/Commands/) : Commander la découvre via $signature (le nom
 * invoqué en CLI, ex. 'niang report:daily') quand aucune commande native ne correspond.
 */
abstract class Command
{
    public static string $signature = 'nom:commande';

    public static string $description = '';

    /** @param list<string> $arguments Les arguments passés après le nom de la commande. */
    abstract public function handle(array $arguments): void;
}
