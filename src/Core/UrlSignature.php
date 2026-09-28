<?php

namespace Niang\Core;

/**
 * Signe une URL (chemin + query string, tel que renvoyé par route()) pour prouver qu'elle vient
 * de l'application sans authentification préalable — reset de mot de passe, vérification
 * d'email, désinscription... HMAC-SHA256 avec APP_KEY (voir AppKey),
 * appliqué ici à une URL plutôt qu'à une valeur de cookie.
 *
 * La signature porte sur les paramètres de requête triés par clé (canonicalisation) : peu
 * importe l'ordre dans lequel le navigateur renvoie la query string, la signature recalculée
 * à la vérification correspond à celle calculée à l'émission.
 */
class UrlSignature
{
    public static function sign(string $url, ?int $expiresInSeconds = null): string
    {
        [$base, $query] = self::split($url);
        unset($query['signature']);

        if ($expiresInSeconds !== null) {
            $query['expires'] = (string) (time() + $expiresInSeconds);
        }

        $query['signature'] = self::hash($base, $query);

        return self::build($base, $query);
    }

    public static function validate(string $url): bool
    {
        [$base, $query] = self::split($url);

        if (!isset($query['signature'])) {
            return false;
        }

        $signature = $query['signature'];
        unset($query['signature']);

        if (isset($query['expires']) && (int) $query['expires'] < time()) {
            return false;
        }

        return hash_equals(self::hash($base, $query), $signature);
    }

    private static function hash(string $base, array $query): string
    {
        ksort($query);
        $canonical = $query ? $base . '?' . http_build_query($query) : $base;

        return hash_hmac('sha256', $canonical, self::key());
    }

    /** @return array{0: string, 1: array<string, string>} */
    private static function split(string $url): array
    {
        $questionMark = strpos($url, '?');

        if ($questionMark === false) {
            return [$url, []];
        }

        $query = [];
        parse_str(substr($url, $questionMark + 1), $query);

        return [substr($url, 0, $questionMark), $query];
    }

    private static function build(string $base, array $query): string
    {
        return $query ? $base . '?' . http_build_query($query) : $base;
    }

    private static function key(): string
    {
        return AppKey::get();
    }
}
