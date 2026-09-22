<?php

namespace Niang\Core;

use Niang\Core\Database\DB;

/**
 * Logique partagée entre `niang health` (CLI) et GET /health|/up (HTTP) — une seule vérification,
 * deux façades. Contrairement à `niang doctor` (statique : PHP, extensions, .env...), ceci teste
 * l'état d'exécution réel : ces services répondent-ils *maintenant* ?
 */
class HealthCheck
{
    /** @return array{status: 'ok'|'error', services: array<string, string>} */
    public static function run(): array
    {
        $services = [
            'database' => self::database(),
            'cache' => self::cache(),
            'storage' => self::storage(),
            'queue' => self::queue(),
        ];

        $status = in_array(false, array_map(static fn (string $s) => $s === 'ok', $services), true)
            ? 'error'
            : 'ok';

        return ['status' => $status, 'services' => $services];
    }

    private static function database(): string
    {
        try {
            DB::connection()->query('SELECT 1');
            return 'ok';
        } catch (\Throwable $e) {
            return 'down : ' . $e->getMessage();
        }
    }

    private static function cache(): string
    {
        try {
            $key = '__health_check__';
            Cache::put($key, true, 5);
            $ok = Cache::get($key) === true;
            Cache::forget($key);

            return $ok ? 'ok' : 'down : lecture après écriture invalide';
        } catch (\Throwable $e) {
            return 'down : ' . $e->getMessage();
        }
    }

    private static function storage(): string
    {
        $path = base_path('storage');

        return is_dir($path) && is_writable($path) ? 'ok' : 'down : storage/ absent ou non accessible en écriture';
    }

    private static function queue(): string
    {
        try {
            Queue::pending();
            return 'ok';
        } catch (\Throwable $e) {
            return 'down : ' . $e->getMessage();
        }
    }
}
