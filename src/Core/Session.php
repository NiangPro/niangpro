<?php

namespace Niang\Core;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        session_set_cookie_params([
            'lifetime' => (int) Config::get('session.lifetime', 120) * 60,
            'path' => '/',
            'httponly' => true,
            'secure' => self::resolveSecureFlag(),
            'samesite' => Config::get('session.same_site', 'Lax'),
        ]);

        session_start();
        self::$started = true;

        $_SESSION['_flash_old'] = $_SESSION['_flash_new'] ?? [];
        $_SESSION['_flash_new'] = [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Stocke une valeur visible uniquement lors de la prochaine requête. */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash_new'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_old'][$key] ?? $default;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
        self::$started = false;
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
