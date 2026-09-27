<?php

declare(strict_types=1);

namespace Niang\Core;

use Niang\Core\Database\DB;
use Niang\Core\Exceptions\ConfigurationException;

/**
 * Cache avec TTL optionnel, sans dépendance à Redis/Memcached. Trois pilotes (CACHE_DRIVER, voir
 * config/cache.php) :
 *  - 'file' (défaut) : un fichier sérialisé par clé dans storage/framework/cache/ ;
 *  - 'database' : table cache_entries, partagée entre plusieurs serveurs web ;
 *  - 'array' : mémoire du process, vidée à la fin de la requête (tests, CLI).
 * La clé est hachée (sha1) : n'importe quelle chaîne est utilisable.
 */
class Cache
{
    /** @var array<string, array{value: mixed, expires: ?int}> pilote 'array' */
    private static array $memory = [];

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

        if (self::driver() === 'array') {
            self::$memory[sha1($key)] = ['value' => $value, 'expires' => $expires];
            return;
        }

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

        self::ensureDirectory($dir);

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

    /**
     * Ajoute $by à une valeur entière (0 si absente) et retourne le résultat. Atomique : deux
     * requêtes simultanées ne perdent pas d'incrément (verrou sur fichier, compare-and-swap en base).
     * Une valeur existante non entière lève une erreur plutôt que d'être écrasée.
     */
    public static function increment(string $key, int $by = 1): int
    {
        return match (self::driver()) {
            'array' => self::incrementInMemory($key, $by),
            'database' => self::incrementInDatabase($key, $by),
            default => self::incrementInFile($key, $by),
        };
    }

    public static function decrement(string $key, int $by = 1): int
    {
        return self::increment($key, -$by);
    }

    public static function forget(string $key): void
    {
        if (self::driver() === 'array') {
            unset(self::$memory[sha1($key)]);
            return;
        }

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
        if (self::driver() === 'array') {
            self::$memory = [];
            return;
        }

        if (self::usesDatabase()) {
            DB::statement('DELETE FROM cache_entries');
            return;
        }

        foreach (glob(base_path('storage/framework/cache') . '/*.{cache,lock}', GLOB_BRACE) ?: [] as $file) {
            unlink($file);
        }
    }

    /** @internal partagé avec RateLimiter : 'file', 'database' ou 'array'. */
    public static function driver(): string
    {
        // Env en repli : la CLI (niang cache:clear, niang health) ne charge pas config/*.php.
        $driver = (string) Config::get('cache.driver', Env::get('CACHE_DRIVER', 'file'));

        if (!in_array($driver, ['file', 'database', 'array'], true)) {
            throw new ConfigurationException("CACHE_DRIVER inconnu : « $driver » (attendu : file, database ou array).");
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
        if (self::driver() === 'array') {
            $payload = self::$memory[sha1($key)] ?? null;

            if ($payload !== null && $payload['expires'] !== null && $payload['expires'] < time()) {
                unset(self::$memory[sha1($key)]);
                return null;
            }

            return $payload;
        }

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

        $raw = @file_get_contents($path);
        $payload = $raw === false ? null : @unserialize($raw);

        if (!is_array($payload) || !array_key_exists('value', $payload)) {
            return null;
        }

        if ($payload['expires'] !== null && $payload['expires'] < time()) {
            @unlink($path);
            return null;
        }

        return $payload;
    }

    private static function incrementInMemory(string $key, int $by): int
    {
        $payload = self::read($key);
        $value = self::integerValue($key, $payload) + $by;
        self::$memory[sha1($key)] = ['value' => $value, 'expires' => $payload['expires'] ?? null];

        return $value;
    }

    /** Verrou exclusif sur un fichier compagnon pendant tout le cycle lecture-écriture. */
    private static function incrementInFile(string $key, int $by): int
    {
        $path = self::path($key);
        $dir = dirname($path);

        self::ensureDirectory($dir);

        $lock = @fopen("$path.lock", 'c');

        if ($lock === false) {
            throw new \RuntimeException("Cache::increment() : impossible d'ouvrir le verrou $path.lock (droits sur storage/framework/cache ?).");
        }

        flock($lock, LOCK_EX);

        try {
            $payload = self::read($key);
            $value = self::integerValue($key, $payload) + $by;
            file_put_contents($path, serialize(['value' => $value, 'expires' => $payload['expires'] ?? null]), LOCK_EX);

            return $value;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Compare-and-swap : l'écriture n'a lieu que si la valeur n'a pas changé depuis la lecture ;
     * sinon on relit et on recommence. Atomique sur SQLite, MySQL et PostgreSQL sans verrou explicite.
     */
    private static function incrementInDatabase(string $key, int $by): int
    {
        $hash = sha1($key);

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $payload = self::read($key);
            $value = self::integerValue($key, $payload) + $by;
            $encoded = base64_encode(serialize($value));

            if ($payload === null) {
                try {
                    DB::statement('INSERT INTO cache_entries (cache_key, value, expiration) VALUES (?, ?, NULL)', [$hash, $encoded]);
                    return $value;
                } catch (\PDOException $e) {
                    if (!str_starts_with((string) $e->getCode(), '23')) {
                        throw $e;
                    }
                    continue; // créée entre-temps par une autre requête : on relit
                }
            }

            $previous = base64_encode(serialize($payload['value']));

            if (DB::affected('UPDATE cache_entries SET value = ? WHERE cache_key = ? AND value = ?', [$encoded, $hash, $previous]) === 1) {
                return $value;
            }
        }

        throw new \RuntimeException("Cache::increment('$key') : trop de modifications simultanées.");
    }

    /** @param array{value: mixed, expires: ?int}|null $payload */
    private static function integerValue(string $key, ?array $payload): int
    {
        if ($payload === null) {
            return 0;
        }

        if (!is_int($payload['value'])) {
            throw new \InvalidArgumentException("Cache::increment('$key') : la valeur existante n'est pas un entier.");
        }

        return $payload['value'];
    }

    /** Deux requêtes simultanées peuvent créer le dossier en même temps : ce n'est pas une erreur. */
    private static function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Impossible de créer le dossier de cache $dir.");
        }
    }

    private static function path(string $key): string
    {
        return base_path('storage/framework/cache/' . sha1($key) . '.cache');
    }
}
