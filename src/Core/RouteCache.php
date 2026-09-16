<?php

namespace Niang\Core;

/**
 * Les routes définies avec une closure ne sont pas sérialisables : elles sont exclues du cache.
 * Utilisez des contrôleurs ([Controller::class, 'method']) pour les routes qui doivent survivre au cache.
 */
class RouteCache
{
    public static function path(): string
    {
        return base_path('storage/framework/routes.php');
    }

    public static function exists(): bool
    {
        return file_exists(self::path());
    }

    /** @return array{routes: array, named: array, fallback: mixed}|null */
    public static function load(): ?array
    {
        return self::exists() ? require self::path() : null;
    }

    /** @return int le nombre de routes effectivement mises en cache (le fallback ne compte pas dedans) */
    public static function store(array $routes, array $namedRoutes, mixed $fallback = null): int
    {
        $cacheable = array_values(array_filter($routes, fn ($route) => !($route['action'] instanceof \Closure)));

        // Même limite que les routes normales : un fallback en closure n'est pas sérialisable.
        if ($fallback instanceof \Closure) {
            $fallback = null;
        }

        $dir = dirname(self::path());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $export = var_export(['routes' => $cacheable, 'named' => $namedRoutes, 'fallback' => $fallback], true);
        file_put_contents(self::path(), "<?php\nreturn $export;\n");

        return count($cacheable);
    }

    public static function clear(): void
    {
        if (self::exists()) {
            unlink(self::path());
        }
    }
}
