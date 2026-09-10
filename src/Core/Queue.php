<?php

namespace Niang\Core;

/**
 * File d'attente sur fichier : chaque job différé est un fichier sérialisé dans
 * storage/framework/queue/, traité par `niang queue:work`. Pas de démon : à lancer via cron,
 * ou en boucle, selon vos besoins de production.
 */
class Queue
{
    public static function push(Job $job): string
    {
        $dir = self::dir();

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $id = uniqid('job_', true);
        file_put_contents("$dir/$id.job", serialize($job));

        return $id;
    }

    /** Traite tous les jobs en attente une fois. @return int le nombre de jobs traités avec succès */
    public static function work(): int
    {
        $processed = 0;

        foreach (glob(self::dir() . '/*.job') ?: [] as $file) {
            $job = @unserialize(file_get_contents($file));
            unlink($file); // retiré avant exécution : au plus une fois, pas de re-traitement en boucle si ça plante

            if (!$job instanceof Job) {
                continue;
            }

            try {
                $job->handle();
                $processed++;
            } catch (\Throwable $e) {
                Log::error('Job échoué : ' . $e->getMessage(), ['job' => $job::class]);
            }
        }

        return $processed;
    }

    public static function pending(): int
    {
        return count(glob(self::dir() . '/*.job') ?: []);
    }

    private static function dir(): string
    {
        return base_path('storage/framework/queue');
    }
}
