<?php

namespace Niang\Core;

use Niang\Core\Exceptions\HttpException;
use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

class Router
{
    private array $routes = [];
    private string $groupPrefix = '';
    private array $groupMiddleware = [];
    private ?string $groupDomain = null;
    private mixed $fallbackAction = null;

    private static array $namedRoutes = [];

    public function get(string $uri, mixed $action, array $middleware = []): RouteRegistration
    {
        return $this->add('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, mixed $action, array $middleware = []): RouteRegistration
    {
        return $this->add('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, mixed $action, array $middleware = []): RouteRegistration
    {
        return $this->add('PUT', $uri, $action, $middleware);
    }

    public function patch(string $uri, mixed $action, array $middleware = []): RouteRegistration
    {
        return $this->add('PATCH', $uri, $action, $middleware);
    }

    public function delete(string $uri, mixed $action, array $middleware = []): RouteRegistration
    {
        return $this->add('DELETE', $uri, $action, $middleware);
    }

    public function options(string $uri, mixed $action, array $middleware = []): RouteRegistration
    {
        return $this->add('OPTIONS', $uri, $action, $middleware);
    }

    /**
     * Enregistrement HEAD explicite — sans ça, une requête HEAD réutilise automatiquement la
     * route GET correspondante et vide simplement le corps de la réponse (dispatch()).
     */
    public function head(string $uri, mixed $action, array $middleware = []): RouteRegistration
    {
        return $this->add('HEAD', $uri, $action, $middleware);
    }

    /** Une seule action pour plusieurs méthodes : $router->match(['GET', 'POST'], '/contact', ...). */
    public function match(array $methods, string $uri, mixed $action, array $middleware = []): RouteRegistration
    {
        $indices = array_map(
            fn (string $method) => $this->pushRoute(strtoupper($method), $uri, $action, $middleware),
            $methods
        );

        return new RouteRegistration($this, $indices);
    }

    /** Exécutée quand aucune route ne correspond (à la place de la 404 par défaut). */
    public function fallback(mixed $action): void
    {
        $this->fallbackAction = $action;
    }

    public function group(array $options, \Closure $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . ($options['prefix'] ?? '');
        $this->groupMiddleware = array_merge($previousMiddleware, $options['middleware'] ?? []);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /** Toutes les routes définies dans $callback ne répondent que sur un hôte correspondant à $pattern (ex: '{tenant}.niangpro.test'). */
    public function domain(string $pattern, \Closure $callback): void
    {
        $previousDomain = $this->groupDomain;
        $this->groupDomain = $pattern;

        $callback($this);

        $this->groupDomain = $previousDomain;
    }

    /** Enregistre les 7 routes REST conventionnelles pointant vers $controller. */
    public function resource(string $uri, string $controller): void
    {
        $uri = trim($uri, '/');
        $name = str_replace('/', '.', $uri);

        $this->get("/$uri", [$controller, 'index'])->name("$name.index");
        $this->get("/$uri/create", [$controller, 'create'])->name("$name.create");
        $this->post("/$uri", [$controller, 'store'])->name("$name.store");
        $this->get("/$uri/{id}", [$controller, 'show'])->name("$name.show");
        $this->get("/$uri/{id}/edit", [$controller, 'edit'])->name("$name.edit");
        $this->put("/$uri/{id}", [$controller, 'update'])->name("$name.update");
        $this->delete("/$uri/{id}", [$controller, 'destroy'])->name("$name.destroy");
    }

    private function add(string $method, string $uri, mixed $action, array $middleware): RouteRegistration
    {
        return new RouteRegistration($this, [$this->pushRoute($method, $uri, $action, $middleware)]);
    }

    private function pushRoute(string $method, string $uri, mixed $action, array $middleware): int
    {
        $uri = $this->groupPrefix . $uri;
        $uri = '/' . trim($uri, '/');

        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
            'domain' => $this->groupDomain,
            'name' => null,
            'pattern' => $this->toPattern($uri),
        ];

        return array_key_last($this->routes);
    }

    public function setRouteName(int $index, string $name): void
    {
        $this->routes[$index]['name'] = $name;
        self::$namedRoutes[$name] = $this->routes[$index]['uri'];
    }

    public function setRouteConstraints(int $index, array $constraints): void
    {
        $this->routes[$index]['pattern'] = $this->toPattern($this->routes[$index]['uri'], $constraints);
    }

    /** Génère l'URL d'une route nommée : route('posts.show', ['id' => 5]) === '/posts/5'. */
    public static function url(string $name, array $params = []): string
    {
        if (!isset(self::$namedRoutes[$name])) {
            throw new \RuntimeException("Route nommée introuvable : $name");
        }

        $uri = self::$namedRoutes[$name];

        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '}', (string) $value, $uri);
        }

        return $uri;
    }

    /** @internal utilisé par le cache de routes (CLI route:cache) */
    public function routes(): array
    {
        return $this->routes;
    }

    /** @internal utilisé par le cache de routes (CLI route:cache) */
    public static function namedRoutes(): array
    {
        return self::$namedRoutes;
    }

    /** @internal utilisé par le cache de routes (CLI route:cache) */
    public function fallbackAction(): mixed
    {
        return $this->fallbackAction;
    }

    /** @internal charge des routes précompilées (les routes à closure ne sont pas cacheables) */
    public function loadFromCache(array $routes, array $namedRoutes, mixed $fallback = null): void
    {
        $this->routes = $routes;
        self::$namedRoutes = $namedRoutes;
        $this->fallbackAction = $fallback;
    }

    private function toPattern(string $uri, array $constraints = []): string
    {
        $pattern = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            fn ($m) => '(?P<' . $m[1] . '>' . ($constraints[$m[1]] ?? '[^/]+') . ')',
            $uri
        );

        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request, Container $container): Response
    {
        $path = '/' . trim($request->uri, '/');
        $host = explode(':', $request->server['HTTP_HOST'] ?? '')[0];

        [$route, $params, $allowedMethods] = $this->matchRoute($request->method, $path, $host);
        $usedGetForHead = false;

        // Pas de route HEAD dédiée : on réutilise le GET correspondant et on vide juste le corps
        // (une route HEAD explicite, elle, garde entièrement la main sur sa réponse).
        if ($route === null && $request->method === 'HEAD') {
            [$route, $params, $allowedMethods] = $this->matchRoute('GET', $path, $host);
            $usedGetForHead = $route !== null;
        }

        if ($route !== null) {
            $request->params = $params;
            $response = $this->runRoute($route, $request, $container);

            return $usedGetForHead ? $response->content('') : $response;
        }

        if (!empty($allowedMethods)) {
            throw new HttpException(405, headers: ['Allow' => implode(', ', array_unique($allowedMethods))]);
        }

        if ($this->fallbackAction !== null) {
            return $this->runRoute(['action' => $this->fallbackAction, 'middleware' => []], $request, $container);
        }

        throw new NotFoundException();
    }

    /** @return array{0: array|null, 1: array, 2: string[]} */
    private function matchRoute(string $method, string $path, string $host): array
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $domainParams = [];

            if (($route['domain'] ?? null) !== null) {
                $matched = $this->matchDomain($route['domain'], $host);

                if ($matched === null) {
                    continue;
                }

                $domainParams = $matched;
            }

            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            if ($route['method'] !== $method) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            $params = array_filter($matches, fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);

            return [$route, [...$domainParams, ...$params], $allowedMethods];
        }

        return [null, [], $allowedMethods];
    }

    private function matchDomain(string $pattern, string $host): ?array
    {
        $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^.]+)', $pattern) . '$#';

        if (!preg_match($regex, $host, $matches)) {
            return null;
        }

        return array_filter($matches, fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);
    }

    private function runRoute(array $route, Request $request, Container $container): Response
    {
        $pipeline = array_reduce(
            array_reverse($route['middleware']),
            function (\Closure $next, string $middleware) use ($container) {
                return function (Request $request) use ($next, $middleware, $container) {
                    $instance = $container->make($middleware);
                    return $instance->handle($request, $next);
                };
            },
            fn (Request $request) => $this->callAction($route['action'], $request, $container)
        );

        $result = $pipeline($request);

        return $result instanceof Response ? $result : Response::html((string) $result);
    }

    private function callAction(mixed $action, Request $request, Container $container): mixed
    {
        if ($action instanceof \Closure) {
            return $container->call($action, ['request' => $request]);
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
            $action = [$class, $method];
        }

        if (is_array($action)) {
            [$class, $method] = $action;
            $controller = $container->make($class);
            return $container->call([$controller, $method], ['request' => $request]);
        }

        throw new \RuntimeException('Action de route invalide.');
    }
}
