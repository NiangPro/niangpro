<?php

namespace Niang\Core;

/** Compteur simple sur fichier (pas de dépendance à Redis/Memcached). */
class RateLimiter
{
    public static function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $data = self::read($key);
        $now = time();

        if ($data === null || $data['resetAt'] <= $now) {
            $data = ['count' => 0, 'resetAt' => $now + $decaySeconds];
        }

        if ($data['count'] >= $maxAttempts) {
            self::write($key, $data);
            return false;
        }

        $data['count']++;
        self::write($key, $data);
        return true;
    }

    public static function availableIn(string $key): int
    {
        $data = self::read($key);
        return $data ? max(0, $data['resetAt'] - time()) : 0;
    }

    private static function path(string $key): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $key);
        $dir = base_path('storage/framework/ratelimits');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return "$dir/$safe.json";
    }

    private static function read(string $key): ?array
    {
        $path = self::path($key);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        return $content ? json_decode($content, true) : null;
    }

    private static function write(string $key, array $data): void
    {
        file_put_contents(self::path($key), json_encode($data), LOCK_EX);
    }
}
