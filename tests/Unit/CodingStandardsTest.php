<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Roadmap §56 : règles de code du cœur, vérifiées plutôt que rappelées. */
class CodingStandardsTest extends TestCase
{
    public function test_every_core_file_declares_strict_types(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('packages'), \FilesystemIterator::SKIP_DOTS));
        $missing = [];

        foreach ($iterator as $file) {
            // helpers.php (fonctions globales des vues) n'a jamais été en mode strict : hors périmètre.
            if ($file->getExtension() === 'php' && $file->getFilename() !== 'helpers.php' && !str_contains((string) file_get_contents($file->getPathname()), 'declare(strict_types=1);')) {
                $missing[] = substr($file->getPathname(), strlen(base_path()) + 1);
            }
        }

        $this->assertSame([], $missing, 'Ajoutez declare(strict_types=1); en tête de ces fichiers du cœur.');
    }
}
