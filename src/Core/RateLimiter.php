<?php

namespace Niang\Core;

/** Compteur simple sur fichier (pas de dépendance à Redis/Memcached). */
class RateLimiter
{
    /**
     * Lecture-puis-écriture protégée par un verrou couvrant tout le cycle (flock() sur le
     * descripteur ouvert, pas seulement au moment d'écrire) : sans ça, deux requêtes concurrentes
     * lisent le même compteur avant qu'aucune n'ait écrit sa mise à jour, et la seconde écriture
     * écrase la première — un incrément silencieusement perdu. Exactement le scénario qu'une
     * attaque par force brute par connexions parallèles (plutôt que séquentielles) exploiterait
     * pour affaiblir cette protection, l'un des usages principaux de ce compteur (voir
     * ThrottleRequests sur /login, /register, /forgot-password).
     */
    public static function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $handle = fopen(self::path($key), 'c+');
        flock($handle, LOCK_EX);

        $content = stream_get_contents($handle);
        $data = $content !== '' ? json_decode($content, true) : null;
        $now = time();

        if (!is_array($data) || $data['resetAt'] <= $now) {
            $data = ['count' => 0, 'resetAt' => $now + $decaySeconds];
        }

        $allowed = $data['count'] < $maxAttempts;

        if ($allowed) {
            $data['count']++;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $allowed;
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
}
