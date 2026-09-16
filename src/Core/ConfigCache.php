<?php

namespace Niang\Core;

/**
 * Fige config/*.php dans un seul fichier — évite de relire et ré-évaluer chaque fichier de config
 * (et les appels env() qu'ils contiennent) à chaque requête. À utiliser en production uniquement :
 * une fois caché, modifier un fichier config/*.php ou .env n'a plus d'effet tant que le cache
 * n'est pas vidé (`niang config:clear`).
 */
class ConfigCache
{
    public static function path(): string
    {
        return base_path('storage/framework/config.php');
    }

    public static function exists(): bool
    {
        return file_exists(self::path());
    }

    public static function load(): ?array
    {
        return self::exists() ? require self::path() : null;
    }

    public static function store(array $items): void
    {
        $dir = dirname(self::path());

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(self::path(), "<?php\n\nreturn " . var_export($items, true) . ";\n");
    }

    public static function clear(): void
    {
        if (self::exists()) {
            unlink(self::path());
        }
    }
}
