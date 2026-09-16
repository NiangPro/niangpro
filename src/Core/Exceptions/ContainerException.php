<?php

namespace Niang\Core\Exceptions;

use Psr\Container\ContainerExceptionInterface;

/** Erreur de résolution du conteneur DI : classe introuvable, dépendance circulaire, paramètre manquant. */
class ContainerException extends \RuntimeException implements ContainerExceptionInterface
{
}
