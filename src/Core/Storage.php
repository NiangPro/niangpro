<?php

namespace Niang\Core;

/**
 * Disque local uniquement, enraciné dans storage/app/ — un driver S3 demanderait un SDK externe,
 * contraire au principe « sans dépendance d'implémentation à l'exécution » du framework.
 */
class Storage
{
    public static function put(string $path, string $contents): bool
    {
        $full = self::resolve($path);
        $dir = dirname($full);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($full, $contents) !== false;
    }

    public static function get(string $path): ?string
    {
        $full = self::resolve($path);

        return is_file($full) ? file_get_contents($full) : null;
    }

    public static function exists(string $path): bool
    {
        return is_file(self::resolve($path));
    }

    public static function delete(string $path): bool
    {
        $full = self::resolve($path);

        return is_file($full) && unlink($full);
    }

    public static function size(string $path): ?int
    {
        $full = self::resolve($path);
        $size = is_file($full) ? filesize($full) : false;

        return $size === false ? null : $size;
    }

    /** Chemin conventionnel, ex: /storage/avatars/1.png — routez-le vers Storage::get() si besoin de le servir. */
    public static function url(string $path): string
    {
        return '/storage/' . self::normalize($path);
    }

    private static function resolve(string $path): string
    {
        return self::root() . '/' . self::normalize($path);
    }

    /** Rejette '..' : sans ça, 'put("../../.env", ...)' écrirait hors de storage/app/. */
    private static function normalize(string $path): string
    {
        $segments = [];

        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                throw new \InvalidArgumentException("Chemin de fichier invalide : « .. » n'est pas autorisé ($path).");
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private static function root(): string
    {
        return base_path('storage/app');
    }
}
