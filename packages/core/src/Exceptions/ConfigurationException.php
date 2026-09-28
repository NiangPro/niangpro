<?php

declare(strict_types=1);

namespace Niang\Core\Exceptions;

/** Pour signaler une configuration critique manquante ou invalide (ex: APP_KEY absente en production). */
class ConfigurationException extends \RuntimeException
{
}
