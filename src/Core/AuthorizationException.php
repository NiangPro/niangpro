<?php

namespace Niang\Core;

use Niang\Core\Exceptions\HttpException;

class AuthorizationException extends HttpException
{
    public function __construct(string $message = '')
    {
        parent::__construct(403, $message);
    }
}
