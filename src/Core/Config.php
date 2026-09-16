<?php

namespace Niang\Core;

class Config
{
    private static ?array $items = null;

    public static function load(string $basePath): void
    {
        $cached = ConfigCache::load();

        if ($cached !== null) {
            self::$items = $cached;
            return;
        }

        self::$items = [];

        foreach (glob($basePath . '/config/*.php') ?: [] as $file) {
            self::$items[basename($file, '.php')] = require $file;
        }
    }

    /** Accès en notation pointée, ex: config('app.providers') */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items ?? [];

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
