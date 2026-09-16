<?php

namespace Niang\Core\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/** Levée par Container::get() (PSR-11) quand l'identifiant n'est ni lié, ni une classe/interface existante. */
class ContainerNotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
