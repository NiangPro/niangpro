<?php

namespace Niang\Core;

/**
 * File d'attente sur fichier : chaque job différé est un fichier sérialisé dans
 * storage/framework/queue/, traité par `niang queue:work`. Pas de démon : à lancer via cron,
 * ou en boucle, selon vos besoins de production.
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
        $processed = 0;
        $now = time();

        foreach (glob(self::dir() . '/*.job') ?: [] as $file) {
            $envelope = self::read($file);

            if ($envelope === null || ($queue !== null && $envelope['queue'] !== $queue) || $envelope['available_at'] > $now) {
                continue;
            }

            unlink($file); // retiré avant exécution : au plus une fois par tentative, pas de re-traitement en boucle si ça plante

            $job = @unserialize($envelope['job']);

            if (!$job instanceof Job) {
                continue;
            }

            try {
                $job->handle();
                $processed++;
            } catch (\Throwable $e) {
                self::handleFailure($envelope, $job, $e);
            }
        }

        return $processed;
    }

    /** @return array<int, array{id: string, queue: string, class: string, error: string, failed_at: string}> */
    public static function failed(): array
    {
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
        $files = glob(self::failedDir() . '/*.job') ?: [];

        foreach ($files as $file) {
            unlink($file);
        }

        return count($files);
    }

    public static function pending(): int
    {
        return count(glob(self::dir() . '/*.job') ?: []);
    }

    private static function store(Job $job, string $queue, int $availableAt): string
    {
        $id = uniqid('job_', true);

        self::write(self::dir(), [
            'id' => $id,
            'queue' => $queue,
            'attempts' => 0,
            'available_at' => $availableAt,
            'job' => serialize($job),
        ]);

        return $id;
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
        $envelope = @unserialize(file_get_contents($file));

        return is_array($envelope) && isset($envelope['job']) ? $envelope : null;
    }

    private static function write(string $dir, array $envelope): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents("$dir/{$envelope['id']}.job", serialize($envelope));
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
