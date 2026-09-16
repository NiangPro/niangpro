<?php

namespace Niang\Core\Exceptions;

class NotFoundException extends HttpException
{
    public function __construct(string $message = '')
    {
        parent::__construct(404, $message);
    }
}
