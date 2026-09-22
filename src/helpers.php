<?php

use Niang\Core\Csrf;
use Niang\Core\Env;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Http\Response;
use Niang\Core\Session;
use Niang\Core\UrlSignature;
use Niang\Core\View;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        static $base;
        $base ??= dirname(__DIR__);
        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return \Niang\Core\Config::get($key, $default);
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        return \Niang\Core\Router::url($name, $params);
    }
}

if (!function_exists('signedRoute')) {
    /** route() + UrlSignature::sign() : lien cliquable sans authentification préalable, expirable. */
    function signedRoute(string $name, array $params = [], ?int $expiresInSeconds = null): string
    {
        return UrlSignature::sign(route($name, $params), $expiresInSeconds);
    }
}

if (!function_exists('view')) {
    function view(string $view, array $data = []): Response
    {
        return \Niang\Core\View::make($view, $data);
    }
}

if (!function_exists('layout')) {
    /** À appeler en haut d'une vue : son contenu rendu sera injecté en tant que $content dans $view. */
    function layout(string $view, array $data = []): void
    {
        View::useLayout($view, $data);
    }
}

if (!function_exists('component')) {
    function component(string $view, array $data = []): string
    {
        return View::component($view, $data);
    }
}

if (!function_exists('e')) {
    /** Échappement HTML explicite : <?= e($valeur) ?> plutôt que htmlspecialchars() partout. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        return Session::getFlash('old', [])[$key] ?? $default;
    }
}

if (!function_exists('flashed')) {
    function flashed(string $key, mixed $default = null): mixed
    {
        return Session::getFlash($key, $default);
    }
}

if (!function_exists('errors')) {
    function errors(?string $key = null): mixed
    {
        $errors = Session::getFlash('errors', []);
        return $key ? ($errors[$key] ?? []) : $errors;
    }
}

if (!function_exists('abort')) {
    /** abort(404), abort(403, 'Message personnalisé')... — intercepté par Niang\Core\Exceptions\Handler. */
    function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message);
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        echo '<pre style="background:#111;color:#0f0;padding:1rem;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        exit(1);
    }
}
