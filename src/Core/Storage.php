<?php

declare(strict_types=1);

namespace Niang\Core;

/**
 * Fichiers de l'application sur le disque choisi par FILESYSTEM_DISK (config/filesystems.php) :
 * 'local' (storage/app/, défaut) ou 's3' (tout service compatible S3, signé en SigV4, sans SDK).
 * Même API sur les deux disques ; seul path() est propre au disque local.
 */
class Storage
{
    private static ?Storage\S3Client $s3 = null;

    public static function put(string $path, string $contents, ?string $contentType = null): bool
    {
        if ($s3 = self::s3()) {
            return $s3->put(self::normalize($path), $contents, $contentType);
        }

        $full = self::resolve($path);
        $dir = dirname($full);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($full, $contents) !== false;
    }

    public static function get(string $path): ?string
    {
        if ($s3 = self::s3()) {
            return $s3->get(self::normalize($path));
        }

        $full = self::resolve($path);

        $contents = is_file($full) ? @file_get_contents($full) : false;

        return $contents === false ? null : $contents;
    }

    public static function exists(string $path): bool
    {
        if ($s3 = self::s3()) {
            return $s3->head(self::normalize($path)) !== null;
        }

        return is_file(self::resolve($path));
    }

    public static function delete(string $path): bool
    {
        if ($s3 = self::s3()) {
            return $s3->delete(self::normalize($path));
        }

        $full = self::resolve($path);

        return is_file($full) && unlink($full);
    }

    public static function size(string $path): ?int
    {
        if ($s3 = self::s3()) {
            return $s3->head(self::normalize($path));
        }

        $full = self::resolve($path);
        $size = is_file($full) ? filesize($full) : false;

        return $size === false ? null : $size;
    }

    /**
     * Disque local : chemin conventionnel (/storage/avatars/1.png), à router vers Storage::get().
     * S3 : URL publique (AWS_URL, un CDN...) ou URL directe de l'objet — lisible seulement si le bucket
     * est public ; sinon, temporaryUrl().
     */
    public static function url(string $path): string
    {
        $normalized = self::normalize($path);

        if (self::s3() !== null) {
            $base = rtrim((string) Config::get('filesystems.s3.url', ''), '/');

            return $base !== '' ? "$base/$normalized" : self::s3()->url($normalized);
        }

        return '/storage/' . $normalized;
    }

    /**
     * Lien de lecture valable $seconds secondes, sans rendre le fichier public : URL pré-signée sur S3,
     * URL signée (UrlSignature, à vérifier par ValidateSignature) sur le disque local.
     */
    public static function temporaryUrl(string $path, int $seconds = 300): string
    {
        $normalized = self::normalize($path);

        if ($s3 = self::s3()) {
            return $s3->presignedUrl($normalized, $seconds);
        }

        return UrlSignature::sign('/storage/' . $normalized, $seconds);
    }

    /** 'local' ou 's3'. */
    public static function disk(): string
    {
        $disk = (string) Config::get('filesystems.disk', Env::get('FILESYSTEM_DISK', 'local'));

        if (!in_array($disk, ['local', 's3'], true)) {
            throw new Exceptions\ConfigurationException("FILESYSTEM_DISK inconnu : « $disk » (attendu : local ou s3).");
        }

        return $disk;
    }

    /** @internal remet le client S3 à zéro (changement de configuration, tests). */
    public static function reset(): void
    {
        self::$s3 = null;
    }

    private static function s3(): ?Storage\S3Client
    {
        if (self::disk() !== 's3') {
            return null;
        }

        $config = (array) Config::get('filesystems.s3', []);

        return self::$s3 ??= new Storage\S3Client($config);
    }

    /**
     * Chemin absolu sur le disque (ex. pour déplacer un fichier envoyé sans le charger en mémoire,
     * voir UploadedFile::storeAs()). Même protection contre « .. » que toutes les autres méthodes.
     */
    public static function path(string $path): string
    {
        if (self::disk() === 's3') {
            throw new \LogicException('Storage::path() : le disque s3 n\'a pas de chemin local. Utilisez get(), put() ou temporaryUrl().');
        }

        return self::resolve($path);
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
