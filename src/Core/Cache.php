<?php

namespace Niang\Core;

use Niang\Core\Database\DB;
use Niang\Core\Exceptions\ConfigurationException;

/**
 * Cache avec TTL optionnel, sans dépendance à Redis/Memcached. Deux pilotes (CACHE_DRIVER, voir
 * config/cache.php) :
 *  - 'file' (défaut) : un fichier sérialisé par clé dans storage/framework/cache/ ;
 *  - 'database' : table cache_entries, partagée entre plusieurs serveurs web.
 * La clé est hachée (sha1) dans les deux cas : n'importe quelle chaîne est utilisable.
 */
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
        $expires = $ttlSeconds !== null ? time() + $ttlSeconds : null;

        if (self::usesDatabase()) {
            $hash = sha1($key);
            DB::transaction(function () use ($hash, $value, $expires): void {
                DB::statement('DELETE FROM cache_entries WHERE cache_key = ?', [$hash]);
                DB::statement(
                    'INSERT INTO cache_entries (cache_key, value, expiration) VALUES (?, ?, ?)',
                    [$hash, base64_encode(serialize($value)), $expires]
                );
            });

            return;
        }

        $path = self::path($key);
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, serialize(['value' => $value, 'expires' => $expires]), LOCK_EX);
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
        if (self::usesDatabase()) {
            DB::statement('DELETE FROM cache_entries WHERE cache_key = ?', [sha1($key)]);
            return;
        }

        $path = self::path($key);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    public static function flush(): void
    {
        if (self::usesDatabase()) {
            DB::statement('DELETE FROM cache_entries');
            return;
        }

        foreach (glob(base_path('storage/framework/cache') . '/*.cache') ?: [] as $file) {
            unlink($file);
        }
    }

    /** @internal partagé avec RateLimiter : 'file' ou 'database'. */
    public static function driver(): string
    {
        // Env en repli : la CLI (niang cache:clear, niang health) ne charge pas config/*.php.
        $driver = (string) Config::get('cache.driver', Env::get('CACHE_DRIVER', 'file'));

        if (!in_array($driver, ['file', 'database'], true)) {
            throw new ConfigurationException("CACHE_DRIVER inconnu : « $driver » (attendu : file ou database).");
        }

        return $driver;
    }

    private static function usesDatabase(): bool
    {
        return self::driver() === 'database';
    }

    /** @return array{value: mixed, expires: ?int}|null */
    private static function read(string $key): ?array
    {
        if (self::usesDatabase()) {
            $row = DB::selectOne('SELECT value, expiration FROM cache_entries WHERE cache_key = ?', [sha1($key)], 'write');

            if ($row === null) {
                return null;
            }

            if ($row['expiration'] !== null && (int) $row['expiration'] < time()) {
                self::forget($key);
                return null;
            }

            $value = @unserialize((string) base64_decode((string) $row['value'], true));

            // serialize(false) === 'b:0;' : false est une valeur légitime, pas un échec de lecture.
            if ($value === false && base64_decode((string) $row['value'], true) !== serialize(false)) {
                return null;
            }

            return ['value' => $value, 'expires' => $row['expiration'] !== null ? (int) $row['expiration'] : null];
        }

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
