<?php

namespace Niang\Core;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Implémentation PSR-3 pour l'injection de dépendances (`LoggerInterface $logger` dans un
 * constructeur, ou du code tiers compatible PSR-3) — délègue à Niang\Core\Log, la façade statique
 * utilisée partout ailleurs dans le framework. Les deux écrivent dans les mêmes fichiers.
 */
class Logger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        Log::log((string) $level, (string) $message, $context);
    }
}
