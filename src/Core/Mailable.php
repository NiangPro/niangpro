<?php

namespace Niang\Core;

abstract class Mailable
{
    abstract public function subject(): string;

    abstract public function body(): string;
}
