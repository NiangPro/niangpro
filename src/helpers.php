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

if (!function_exists('json_for_html')) {
    /**
     * Encode $data en JSON puis échappe pour un usage sûr en attribut HTML — sensible en sécurité
     * (XSS), deux couches nécessaires et complémentaires :
     *  1. json_encode(..., JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) protège
     *     le CONTENU JSON lui-même : JSON_HEX_TAG empêche un `</script>` dans les données de
     *     refermer une balise <script> environnante, JSON_HEX_QUOT/JSON_HEX_APOS/JSON_HEX_AMP
     *     neutralisent les guillemets et esperluettes qui apparaissent DANS les valeurs.
     *  2. htmlspecialchars() sur le résultat protège le CONTENEUR HTML : les guillemets
     *     STRUCTURELS du JSON lui-même (`{"clé":"valeur"}`) restent des caractères `"` littéraux
     *     après l'étape 1 — seuls ceux venant des données sont neutralisés — et cassent un
     *     attribut délimité par des guillemets dès que $data est un tableau/objet (vérifié avec
     *     un vrai navigateur : sans cette seconde couche, `data-props="{"a":"b"}"` se referme au
     *     premier `"` après `{`, et tout le reste devient des attributs HTML arbitraires). Le
     *     navigateur décode les entités HTML d'un attribut à la lecture (dataset, getAttribute) :
     *     JSON.parse() reçoit donc le JSON d'origine, intact.
     *
     * Usage type, pour Alpine.js ou pour hydrater un composant Vue/React monté côté client :
     *
     *   <div data-props="<?= json_for_html($props) ?>" x-data="JSON.parse($el.dataset.props)">
     */
    function json_for_html(mixed $data): string
    {
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        if ($json === false) {
            throw new \JsonException('json_for_html() : données non encodables en JSON — ' . json_last_error_msg());
        }

        return htmlspecialchars($json, ENT_QUOTES, 'UTF-8');
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
