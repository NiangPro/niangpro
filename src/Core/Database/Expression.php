<?php

namespace Niang\Core\Database;

/** Enrobe un fragment SQL brut (ex: CURRENT_TIMESTAMP) pour qu'il ne soit pas traité comme une valeur littérale. */
class Expression
{
    public function __construct(public readonly string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
