<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Roadmap §56 : règles de code du cœur, vérifiées plutôt que rappelées. */
class CodingStandardsTest extends TestCase
{
    public function test_every_core_file_declares_strict_types(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('src/Core'), \FilesystemIterator::SKIP_DOTS));
        $missing = [];

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && !str_contains((string) file_get_contents($file->getPathname()), 'declare(strict_types=1);')) {
                $missing[] = substr($file->getPathname(), strlen(base_path()) + 1);
            }
        }

        $this->assertSame([], $missing, 'Ajoutez declare(strict_types=1); en tête de ces fichiers du cœur.');
    }
}
