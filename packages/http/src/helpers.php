<?php

// Fonctions globales des vues et des requêtes : routes nommées, vues, formulaires, messages flash.

use Niang\Core\Csrf;
use Niang\Core\Http\Response;
use Niang\Core\Session;
use Niang\Core\UrlSignature;
use Niang\Core\View;

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

if (!function_exists('field')) {
    /**
     * Raccourci pour component('components/field', [...]) : les deux clés 'name' et 'label' sont
     * obligatoires à chaque appel et reviennent dans presque toutes les vues de formulaire — les
     * répéter en tableau associatif à chaque champ est le principal bruit visuel d'un formulaire.
     *
     *   <?= field('email', 'Adresse email', ['type' => 'email', 'autocomplete' => 'email']) ?>
     *
     * plutôt que :
     *
     *   <?= component('components/field', ['name' => 'email', 'label' => 'Adresse email', 'type' => 'email', 'autocomplete' => 'email']) ?>
     *
     * $options accepte les mêmes clés que le composant (type, rows, value, autocomplete, required) —
     * ce n'est qu'un raccourci d'appel, pas un nouveau mécanisme : resources/views/components/field.php
     * reste le seul endroit qui décide du HTML produit.
     */
    function field(string $name, string $label, array $options = []): string
    {
        return component('components/field', ['name' => $name, 'label' => $label, ...$options]);
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

if (!function_exists('vite_asset')) {
    /**
     * URL réelle d'un asset construit par Vite (voir Niang\Core\ViteAssets et le README, section
     * Vite) — NiangPro n'embarque aucun outillage de build, ce helper se contente de lire ce que
     * Vite a produit dans public/build/ (ou de basculer sur son serveur de dev via public/hot).
     * N'exige rien d'installé pour que le reste du framework fonctionne : seul l'appel explicite
     * de ce helper suppose que Vite est configuré, sans quoi il échoue avec un message clair.
     */
    function vite_asset(string $entry): string
    {
        return (new \Niang\Core\ViteAssets(base_path('public')))->asset($entry);
    }
}
