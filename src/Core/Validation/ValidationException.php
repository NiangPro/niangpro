<?php

namespace Niang\Core\Validation;

use Niang\Core\Lang;

class ValidationException extends \RuntimeException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct(Lang::get('validation.failed'));
    }
}
