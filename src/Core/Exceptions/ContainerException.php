<?php

declare(strict_types=1);

namespace Niang\Core\Exceptions;

use Psr\Container\ContainerExceptionInterface;

/** Erreur de résolution du conteneur DI : classe introuvable, dépendance circulaire, paramètre manquant. */
class ContainerException extends \RuntimeException implements ContainerExceptionInterface
{
}
