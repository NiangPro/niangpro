<?php

namespace Niang\Core\Exceptions;

/**
 * Disponible pour signaler une erreur de base de données applicative (ex: contrainte métier
 * violée). Les erreurs PDO elles-mêmes ne sont pas systématiquement enrobées dedans : le Handler
 * reconnaît aussi \PDOException directement, pour ne jamais divulguer le SQL ou la connexion en
 * production tout en gardant le message original en développement.
 */
class DatabaseException extends \RuntimeException
{
}
