<?php

namespace Niang\Core\Exceptions;

class AuthenticationException extends HttpException
{
    public function __construct(string $message = '')
    {
        parent::__construct(401, $message);
    }
}
