<?php

namespace Niang\Core;

use Niang\Core\Http\Request;

/**
 * Calcule les en-têtes CORS pour une requête, à partir de config/cors.php. Logique pure (pas de
 * middleware ici) — App\Middleware\HandleCors l'applique aux réponses et court-circuite le préflight.
 */
class Cors
{
    /** @return array<string, string> en-têtes à ajouter à la réponse ; vide si l'origine n'est pas autorisée ou absente. */
    public static function headersFor(Request $request): array
    {
        $origin = (string) $request->header('Origin', '');

        if ($origin === '' || !self::originAllowed($origin)) {
            return [];
        }

        $supportsCredentials = (bool) Config::get('cors.supports_credentials', false);
        $allowedOrigins = Config::get('cors.allowed_origins', []);

        $headers = [
            // Access-Control-Allow-Origin: '*' est interdit par les navigateurs dès que les credentials
            // sont activés — on reflète alors toujours l'origine exacte de la requête.
            'Access-Control-Allow-Origin' => (!$supportsCredentials && in_array('*', $allowedOrigins, true)) ? '*' : $origin,
            'Access-Control-Allow-Methods' => implode(', ', Config::get('cors.allowed_methods', [])),
            'Access-Control-Allow-Headers' => implode(', ', Config::get('cors.allowed_headers', [])),
            'Vary' => 'Origin',
        ];

        if ($supportsCredentials) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        $exposed = Config::get('cors.exposed_headers', []);
        if ($exposed) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $exposed);
        }

        $maxAge = Config::get('cors.max_age', 0);
        if ($maxAge) {
            $headers['Access-Control-Max-Age'] = (string) $maxAge;
        }

        return $headers;
    }

    public static function isPreflight(Request $request): bool
    {
        return $request->isMethod('OPTIONS') && $request->header('Access-Control-Request-Method') !== null;
    }

    private static function originAllowed(string $origin): bool
    {
        $allowed = Config::get('cors.allowed_origins', []);

        if (in_array($origin, $allowed, true)) {
            return true;
        }

        if (!in_array('*', $allowed, true)) {
            return false;
        }

        // '*' n'autorise jamais une origine reflétée avec des identifiants (cookies, en-tête
        // Authorization) : ce serait équivalent à désactiver CORS pour n'importe quel site tout
        // en gardant les identifiants de l'utilisateur actif. config/cors.php déconseille déjà
        // cette combinaison en commentaire ("supports_credentials=true seulement avec des
        // origines explicites") ; sans cette garde, rien ne l'empêchait réellement.
        return !(bool) Config::get('cors.supports_credentials', false);
    }
}
