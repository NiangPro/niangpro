<?php

namespace Niang\Core;

class Cookie
{
    public static function set(string $name, string $value, int $minutes = 60): void
    {
        setcookie($name, self::sign($value), [
            'expires' => time() + $minutes * 60,
            'path' => '/',
            'httponly' => true,
            'secure' => self::resolveSecureFlag(),
            'samesite' => Config::get('session.same_site', 'Lax'),
        ]);
    }

    public static function get(string $name, mixed $default = null): mixed
    {
        if (!isset($_COOKIE[$name])) {
            return $default;
        }

        $value = self::unsign($_COOKIE[$name]);

        return $value ?? $default;
    }

    public static function forget(string $name): void
    {
        setcookie($name, '', ['expires' => time() - 3600, 'path' => '/']);
    }

    private static function sign(string $value): string
    {
        $signature = hash_hmac('sha256', $value, self::key());
        return $signature . '.' . base64_encode($value);
    }

    private static function unsign(string $signed): ?string
    {
        [$signature, $encoded] = array_pad(explode('.', $signed, 2), 2, '');
        $value = base64_decode($encoded ?: '', true);

        if ($value === false || $signature === '') {
            return null;
        }

        $expected = hash_hmac('sha256', $value, self::key());

        return hash_equals($expected, $signature) ? $value : null;
    }

    private static function key(): string
    {
        return Env::get('APP_KEY', 'niangpro-insecure-default-key');
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
