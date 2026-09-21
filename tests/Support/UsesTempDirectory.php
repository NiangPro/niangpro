<?php

namespace Tests\Support;

/** Dossiers temporaires isolés pour les tests qui écrivent sur le disque (générateurs, scaffolding). */
trait UsesTempDirectory
{
    /** @var list<string> */
    private array $tempDirectories = [];

    protected function makeTempDirectory(): string
    {
        $path = sys_get_temp_dir() . '/niang-test-' . bin2hex(random_bytes(6));
        mkdir($path, 0755, true);

        return $this->tempDirectories[] = $path;
    }

    protected function writeFile(string $base, string $relativePath, string $content = ''): void
    {
        $path = $base . '/' . $relativePath;

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $content);
    }

    /** À appeler depuis tearDown(). */
    protected function removeTempDirectories(): void
    {
        foreach ($this->tempDirectories as $directory) {
            self::deleteDirectory($directory);
        }

        $this->tempDirectories = [];
    }

    protected static function deleteDirectory(string $path): void
    {
        if (!is_dir($path) || is_link($path)) {
            @unlink($path);

            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
