<?php

declare(strict_types=1);

namespace Niang\Core;

class Env
{
    /** Mode debug : APP_DEBUG=true (défaut hors production), jamais en production quoi qu'indique APP_DEBUG. */
    public static function debug(): bool
    {
        if (self::get('APP_ENV') === 'production') {
            return false;
        }

        return self::get('APP_DEBUG', 'true') === 'true';
    }

    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        $lines = file_exists($path) ? @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : false;

        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");

                // Une variable déjà présente dans l'environnement réel (CI, Docker, hébergeur...)
                // garde la priorité sur le fichier : le fichier ne fournit que des valeurs par défaut.
                if (getenv($key) === false) {
                    putenv("$key=$value");
                }
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        return $value !== false ? $value : $default;
    }
}
