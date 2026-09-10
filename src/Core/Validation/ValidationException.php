<?php

namespace Niang\Core\Validation;

class ValidationException extends \RuntimeException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct('La validation a échoué.');
    }
}
