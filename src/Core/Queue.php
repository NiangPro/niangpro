<?php

namespace Niang\Core;

use Niang\Core\Database\DB;
use Niang\Core\Exceptions\ConfigurationException;

/**
 * File d'attente, traitée par `niang queue:work`. Pas de démon : à lancer via cron (ou le
 * planificateur), ou en boucle, selon vos besoins de production. Trois pilotes (QUEUE_DRIVER, voir
 * config/queue.php) : 'file' (défaut, un fichier sérialisé par job dans storage/framework/queue/),
 * 'database' (tables jobs et failed_jobs, partagées entre serveurs) et 'sync' (exécution immédiate).
 *
 * Un job qui échoue est retenté jusqu'à Job::$tries fois, avec un backoff exponentiel
 * (10s, 20s, 40s...) entre chaque tentative, puis déplacé vers storage/framework/queue/failed/
 * (voir failed()/retry()/flush(), et `niang queue:failed`/`queue:retry`/`queue:flush`).
 */
class Queue
{
    private const BASE_BACKOFF_SECONDS = 10;

    public static function push(Job $job, string $queue = 'default'): string
    {
        if (self::driver() === 'sync') {
            $job->handle();
            return uniqid('job_', true);
        }

        return self::store($job, $queue, time());
    }

    /** Comme push(), mais ne devient éligible au traitement qu'après $delaySeconds. */
    public static function later(int $delaySeconds, Job $job, string $queue = 'default'): string
    {
        return self::store($job, $queue, time() + $delaySeconds);
    }

    /**
     * Traite une fois tous les jobs dus de $queue (toutes les files si null).
     * @return int le nombre de jobs traités avec succès
     */
    public static function work(?string $queue = null): int
    {
        if (self::driver() === 'database') {
            return self::workDatabase($queue);
        }

        $processed = 0;
        $now = time();

        foreach (glob(self::dir() . '/*.job') ?: [] as $file) {
            $envelope = self::read($file);

            if ($envelope === null || ($queue !== null && $envelope['queue'] !== $queue) || $envelope['available_at'] > $now) {
                continue;
            }

            // Réclame le fichier avant de l'exécuter, de façon atomique (rename() sur un même
            // système de fichiers l'est) : si un autre worker (`queue:work` lancé en parallèle,
            // un schéma de production courant pour paralléliser le traitement) a lu le même
            // fichier entre-temps, son propre rename() échoue et il passe au suivant plutôt que
            // d'exécuter deux fois le même job — l'ancien code supprimait le fichier APRÈS
            // l'avoir lu, sans jamais vérifier que la suppression avait réussi ni que personne
            // d'autre ne l'avait déjà traité.
            $claimed = "$file.processing";

            if (!@rename($file, $claimed)) {
                continue;
            }

            $job = @unserialize($envelope['job']);

            if (!$job instanceof Job) {
                @unlink($claimed);
                continue;
            }

            try {
                $job->handle();
                $processed++;
                @unlink($claimed);
            } catch (\Throwable $e) {
                @unlink($claimed);
                self::handleFailure($envelope, $job, $e);
            }
        }

        return $processed;
    }

    /** @return array<int, array{id: string, queue: string, class: string, error: string, failed_at: string}> */
    public static function failed(): array
    {
        if (self::driver() === 'database') {
            return array_map(fn (array $row) => [
                'id' => $row['job_id'],
                'queue' => $row['queue'],
                'class' => ($job = self::decode($row['payload'])) instanceof Job ? $job::class : 'inconnu',
                'error' => (string) $row['error'],
                'failed_at' => (string) $row['failed_at'],
            ], DB::select('SELECT * FROM failed_jobs ORDER BY id', [], 'write'));
        }

        $failed = [];

        foreach (glob(self::failedDir() . '/*.job') ?: [] as $file) {
            $envelope = self::read($file);

            if ($envelope === null) {
                continue;
            }

            $job = @unserialize($envelope['job']);

            $failed[] = [
                'id' => $envelope['id'],
                'queue' => $envelope['queue'],
                'class' => $job instanceof Job ? $job::class : 'inconnu',
                'error' => $envelope['error'] ?? '',
                'failed_at' => $envelope['failed_at'] ?? '',
            ];
        }

        return $failed;
    }

    /** Remet un job échoué dans la file, attempts réinitialisé, disponible immédiatement. */
    public static function retry(string $id): bool
    {
        if (self::driver() === 'database') {
            $row = DB::selectOne('SELECT * FROM failed_jobs WHERE job_id = ?', [$id], 'write');

            if ($row === null) {
                return false;
            }

            DB::transaction(function () use ($row): void {
                DB::statement(
                    'INSERT INTO jobs (job_id, queue, payload, attempts, available_at) VALUES (?, ?, ?, 0, ?)',
                    [$row['job_id'], $row['queue'], $row['payload'], time()]
                );
                DB::statement('DELETE FROM failed_jobs WHERE job_id = ?', [$row['job_id']]);
            });

            return true;
        }

        $failedFile = self::failedDir() . "/$id.job";
        $envelope = is_file($failedFile) ? self::read($failedFile) : null;

        if ($envelope === null) {
            return false;
        }

        unset($envelope['failed_at'], $envelope['error']);
        $envelope['attempts'] = 0;
        $envelope['available_at'] = time();

        self::write(self::dir(), $envelope);
        unlink($failedFile);

        return true;
    }

    /** Supprime définitivement tous les jobs échoués. @return int le nombre de jobs supprimés */
    public static function flush(): int
    {
        if (self::driver() === 'database') {
            return DB::affected('DELETE FROM failed_jobs');
        }

        $files = glob(self::failedDir() . '/*.job') ?: [];

        foreach ($files as $file) {
            unlink($file);
        }

        return count($files);
    }

    public static function pending(): int
    {
        if (self::driver() === 'database') {
            return (int) (DB::selectOne('SELECT COUNT(*) AS n FROM jobs', [], 'write')['n'] ?? 0);
        }

        return count(glob(self::dir() . '/*.job') ?: []);
    }

    /** @internal vide la file (en attente et échouée) — appelé par TestCase entre deux tests. */
    public static function reset(): void
    {
        if (self::driver() === 'database') {
            DB::statement('DELETE FROM jobs');
            DB::statement('DELETE FROM failed_jobs');
            return;
        }

        foreach ([self::dir(), self::failedDir()] as $dir) {
            foreach (glob($dir . '/*.job') ?: [] as $file) {
                unlink($file);
            }
        }
    }

    private static function store(Job $job, string $queue, int $availableAt): string
    {
        $id = uniqid('job_', true);

        if (self::driver() === 'database') {
            DB::statement(
                'INSERT INTO jobs (job_id, queue, payload, attempts, available_at) VALUES (?, ?, ?, 0, ?)',
                [$id, $queue, base64_encode(serialize($job)), $availableAt]
            );

            return $id;
        }

        self::write(self::dir(), [
            'id' => $id,
            'queue' => $queue,
            'attempts' => 0,
            'available_at' => $availableAt,
            'job' => serialize($job),
        ]);

        return $id;
    }

    /** 'file', 'database' ou 'sync' ; une valeur inconnue est une erreur, pas un repli silencieux. */
    public static function driver(): string
    {
        $driver = (string) Config::get('queue.driver', Env::get('QUEUE_DRIVER', 'file'));

        if (!in_array($driver, ['file', 'database', 'sync'], true)) {
            throw new ConfigurationException("QUEUE_DRIVER inconnu : « $driver » (attendu : file, database ou sync).");
        }

        return $driver;
    }

    private static function workDatabase(?string $queue): int
    {
        $processed = 0;
        $now = time();
        $stale = $now - (int) Config::get('queue.retry_after', 600);

        $sql = 'SELECT id FROM jobs WHERE available_at <= ? AND (reserved_at IS NULL OR reserved_at < ?)';
        $bindings = [$now, $stale];

        if ($queue !== null) {
            $sql .= ' AND queue = ?';
            $bindings[] = $queue;
        }

        foreach (DB::select("$sql ORDER BY id", $bindings, 'write') as $candidate) {
            // Réclamation atomique : un seul worker (même sur une autre machine) obtient la ligne.
            $claimed = DB::affected(
                'UPDATE jobs SET reserved_at = ? WHERE id = ? AND (reserved_at IS NULL OR reserved_at < ?)',
                [$now, $candidate['id'], $stale]
            );

            if ($claimed !== 1) {
                continue;
            }

            $row = DB::selectOne('SELECT * FROM jobs WHERE id = ?', [$candidate['id']], 'write');
            $job = $row !== null ? self::decode($row['payload']) : null;

            if (!$job instanceof Job) {
                DB::statement('DELETE FROM jobs WHERE id = ?', [$candidate['id']]);
                continue;
            }

            try {
                $job->handle();
                $processed++;
                DB::statement('DELETE FROM jobs WHERE id = ?', [$row['id']]);
            } catch (\Throwable $e) {
                self::handleDatabaseFailure($row, $job, $e);
            }
        }

        return $processed;
    }

    private static function handleDatabaseFailure(array $row, Job $job, \Throwable $e): void
    {
        $attempts = (int) $row['attempts'] + 1;

        Log::error('Job échoué : ' . $e->getMessage(), ['job' => $job::class, 'attempts' => $attempts]);

        if ($attempts >= $job->tries) {
            DB::transaction(function () use ($row, $e): void {
                DB::statement(
                    'INSERT INTO failed_jobs (job_id, queue, payload, error, failed_at) VALUES (?, ?, ?, ?, ?)',
                    [$row['job_id'], $row['queue'], $row['payload'], $e->getMessage(), date('Y-m-d H:i:s')]
                );
                DB::statement('DELETE FROM jobs WHERE id = ?', [$row['id']]);
            });

            return;
        }

        DB::statement(
            'UPDATE jobs SET attempts = ?, reserved_at = NULL, available_at = ? WHERE id = ?',
            [$attempts, time() + self::BASE_BACKOFF_SECONDS * (2 ** ($attempts - 1)), $row['id']]
        );
    }

    private static function decode(string $payload): mixed
    {
        $raw = base64_decode($payload, true);

        return $raw === false ? null : @unserialize($raw);
    }

    private static function handleFailure(array $envelope, Job $job, \Throwable $e): void
    {
        $envelope['attempts']++;

        Log::error('Job échoué : ' . $e->getMessage(), ['job' => $job::class, 'attempts' => $envelope['attempts']]);

        if ($envelope['attempts'] >= $job->tries) {
            $envelope['failed_at'] = date('Y-m-d H:i:s');
            $envelope['error'] = $e->getMessage();
            self::write(self::failedDir(), $envelope);
            return;
        }

        $envelope['available_at'] = time() + self::BASE_BACKOFF_SECONDS * (2 ** ($envelope['attempts'] - 1));
        self::write(self::dir(), $envelope);
    }

    private static function read(string $file): ?array
    {
        $raw = @file_get_contents($file);
        $envelope = $raw === false ? null : @unserialize($raw);

        return is_array($envelope) && isset($envelope['job']) ? $envelope : null;
    }

    private static function write(string $dir, array $envelope): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents("$dir/{$envelope['id']}.job", serialize($envelope), LOCK_EX);
    }

    private static function dir(): string
    {
        return base_path('storage/framework/queue');
    }

    private static function failedDir(): string
    {
        return base_path('storage/framework/queue/failed');
    }
}
