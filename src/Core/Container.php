<?php

namespace Niang\Core;

use Niang\Core\Http\Request;
use Niang\Core\Validation\FormRequest;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, \Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]($this);
        }

        if (!class_exists($abstract)) {
            throw new \RuntimeException("Impossible de résoudre [$abstract] : classe introuvable.");
        }

        $reflection = new \ReflectionClass($abstract);

        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("[$abstract] n'est pas instanciable.");
        }

        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $abstract();
        }

        $dependencies = array_map(
            fn (\ReflectionParameter $param) => $this->resolveParameter($param),
            $constructor->getParameters()
        );

        return $reflection->newInstanceArgs($dependencies);
    }

    public function call(callable $callback, array $extraParams = []): mixed
    {
        $reflection = is_array($callback)
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);

        $args = array_map(
            fn (\ReflectionParameter $param) => $this->resolveParameter($param, $extraParams),
            $reflection->getParameters()
        );

        return $callback(...$args);
    }

    private function resolveParameter(\ReflectionParameter $param, array $extraParams = []): mixed
    {
        $name = $param->getName();
        $type = $param->getType();

        // Seul un type simple (ReflectionNamedType) peut être auto-résolu ; un paramètre union/intersection
        // (ex: int|string $x) tombe dans la résolution par nom / valeur par défaut ci-dessous.
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            $className = $type->getName();

            // Une valeur nommée correspond seulement si elle est compatible avec le type déclaré
            // (sinon `ContactRequest $request` récupérerait le Request de base au lieu d'être résolu/validé).
            if (array_key_exists($name, $extraParams) && $extraParams[$name] instanceof $className) {
                return $extraParams[$name];
            }

            foreach ($extraParams as $value) {
                if ($value instanceof $className) {
                    return $value;
                }
            }

            // Une FormRequest se construit à partir de la requête en cours (méthode, données, params
            // de route), pas via l'auto-wiring générique de make() — Request n'a pas de constructeur vide.
            if (is_a($className, FormRequest::class, true)) {
                return $this->makeFormRequest($className, $extraParams['request'] ?? null);
            }

            return $this->make($className);
        }

        if (array_key_exists($name, $extraParams)) {
            return $extraParams[$name];
        }

        // route params by name, e.g. function show($id)
        $request = $extraParams['request'] ?? null;
        if ($request instanceof Request && array_key_exists($name, $request->params)) {
            return $request->params[$name];
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        throw new \RuntimeException("Impossible de résoudre le paramètre [\$$name].");
    }

    /** @param class-string<FormRequest> $className */
    private function makeFormRequest(string $className, ?Request $original): FormRequest
    {
        $instance = $original instanceof Request
            ? new $className(
                $original->method,
                $original->uri,
                $original->query,
                $original->body,
                $original->server,
                $original->headers,
                $original->params
            )
            : new $className('GET', '/');

        $instance->validateResolved();

        return $instance;
    }
}
