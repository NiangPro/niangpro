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

    /**
     * Remplace un paramètre de route par la ligne correspondante du modèle (404 si absente), après
     * les middlewares : ->bind(['post' => Post::class]) cherche par id, ->bind(['post' => Post::class . ':slug'])
     * par une autre colonne. Le contrôleur la reçoit sous le même nom : function show(array $post).
     *
     * @param array<string, string> $bindings
     */
    public function bind(array $bindings): static
    {
        foreach ($bindings as $parameter => $binding) {
            [$model, $column] = array_pad(explode(':', $binding, 2), 2, 'id');

            if (!is_subclass_of($model, Database\Model::class)) {
                throw new \InvalidArgumentException("bind() : « $model » n'est pas un modèle (Niang\\Core\\Database\\Model).");
            }

            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column) !== 1) {
                throw new \InvalidArgumentException("bind() : colonne invalide « $column ».");
            }

            foreach ($this->indices as $index) {
                $this->router->setRouteBinding($index, $parameter, $model, $column);
            }
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
