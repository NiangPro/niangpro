<?php

namespace Niang\Core;

class RouteRegistration
{
    /** @param int[] $indices une seule route normalement, plusieurs pour $router->match([...]) */
    public function __construct(private Router $router, private array $indices)
    {
    }

    public function name(string $name): static
    {
        foreach ($this->indices as $index) {
            $this->router->setRouteName($index, $name);
        }

        return $this;
    }

    /** Contraintes regex par paramètre, ex: ->where(['id' => '[0-9]+']) */
    public function where(array $constraints): static
    {
        foreach ($this->indices as $index) {
            $this->router->setRouteConstraints($index, $constraints);
        }

        return $this;
    }
}
