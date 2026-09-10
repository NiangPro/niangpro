<?php

namespace Niang\Core;

/** Cache fichier simple (pas de dépendance à Redis/Memcached), avec TTL optionnel. */
class Cache
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $payload = self::read($key);
        return $payload !== null ? $payload['value'] : $default;
    }

    public static function has(string $key): bool
    {
        return self::read($key) !== null;
    }

    public static function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $path = self::path($key);
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $payload = ['value' => $value, 'expires' => $ttlSeconds !== null ? time() + $ttlSeconds : null];
        file_put_contents($path, serialize($payload), LOCK_EX);
    }

    /** Retourne la valeur en cache, ou exécute $callback et met le résultat en cache. */
    public static function remember(string $key, ?int $ttlSeconds, \Closure $callback): mixed
    {
        $payload = self::read($key);

        if ($payload !== null) {
            return $payload['value'];
        }

        $value = $callback();
        self::put($key, $value, $ttlSeconds);

        return $value;
    }

    public static function forget(string $key): void
    {
        $path = self::path($key);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    public static function flush(): void
    {
        foreach (glob(base_path('storage/framework/cache') . '/*.cache') ?: [] as $file) {
            unlink($file);
        }
    }

    private static function read(string $key): ?array
    {
        $path = self::path($key);

        if (!file_exists($path)) {
            return null;
        }

        $payload = @unserialize(file_get_contents($path));

        if (!is_array($payload) || !array_key_exists('value', $payload)) {
            return null;
        }

        if ($payload['expires'] !== null && $payload['expires'] < time()) {
            @unlink($path);
            return null;
        }

        return $payload;
    }

    private static function path(string $key): string
    {
        return base_path('storage/framework/cache/' . sha1($key) . '.cache');
    }
}
