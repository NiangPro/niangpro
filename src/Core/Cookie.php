<?php

namespace Niang\Core;

/**
 * Cookies chiffrés (Crypt, AES-256-GCM) : le navigateur ne peut ni lire ni modifier la valeur. Le
 * chiffré est lié au nom du cookie (une valeur valide pour « panier » est refusée sous
 * « remember ») et porte sa propre date d'expiration, vérifiée côté serveur — un cookie recopié
 * après son expiration est refusé même si le navigateur l'a gardé.
 *
 * Les cookies signés par une version précédente du framework (HMAC, en clair) ne sont plus
 * acceptés : Cookie::get() retourne la valeur par défaut, comme pour un cookie absent.
 */
class Cookie
{
    public static function set(string $name, string $value, int $minutes = 60): void
    {
        setcookie($name, self::encode($name, $value, $minutes), [
            'expires' => time() + $minutes * 60,
            'path' => '/',
            'httponly' => true,
            'secure' => self::resolveSecureFlag(),
            'samesite' => Config::get('session.same_site', 'Lax'),
        ]);
    }

    public static function get(string $name, mixed $default = null): mixed
    {
        if (!isset($_COOKIE[$name]) || !is_string($_COOKIE[$name])) {
            return $default;
        }

        return self::decode($name, $_COOKIE[$name]) ?? $default;
    }

    public static function forget(string $name): void
    {
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'secure' => self::resolveSecureFlag(),
            'samesite' => Config::get('session.same_site', 'Lax'),
        ]);
    }

    private static function encode(string $name, string $value, int $minutes): string
    {
        $payload = json_encode(['v' => $value, 'e' => time() + $minutes * 60], JSON_THROW_ON_ERROR);

        return Crypt::encrypt($payload, "cookie:$name");
    }

    private static function decode(string $name, string $encrypted): ?string
    {
        $payload = Crypt::decrypt($encrypted, "cookie:$name");
        $data = $payload !== null ? json_decode($payload, true) : null;

        if (!is_array($data) || !is_string($data['v'] ?? null) || !is_int($data['e'] ?? null) || $data['e'] < time()) {
            return null;
        }

        return $data['v'];
    }

    /** Devine si la requête courante est en HTTPS, sauf si config/session.php force explicitement une valeur. */
    private static function resolveSecureFlag(): bool
    {
        $configured = Config::get('session.secure');

        if ($configured !== null) {
            return (bool) $configured;
        }

        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
