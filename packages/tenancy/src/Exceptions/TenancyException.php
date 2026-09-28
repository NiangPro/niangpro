<?php

declare(strict_types=1);

namespace Niang\Core\Exceptions;

/** Modèle par locataire interrogé sans locataire courant, ou locataire introuvable. */
class TenancyException extends \RuntimeException
{
}
