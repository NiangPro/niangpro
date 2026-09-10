<?php

namespace Niang\Core;

class RouteRegistration
{
    public function __construct(private Router $router, private int $index)
    {
    }

    public function name(string $name): static
    {
        $this->router->setRouteName($this->index, $name);
        return $this;
    }

    /** Contraintes regex par paramètre, ex: ->where(['id' => '[0-9]+']) */
    public function where(array $constraints): static
    {
        $this->router->setRouteConstraints($this->index, $constraints);
        return $this;
    }
}
