<?php

namespace Tests\Unit\Database;

use Niang\Core\Database\DB;
use PHPUnit\Framework\TestCase;

/**
 * DB::isAbsolutePath() est privée : décide si DB_DATABASE (sqlite) doit être préfixé de
 * base_path() ou utilisé tel quel. Testée par réflexion (voir le motif déjà utilisé pour
 * Psr7Bridge::requireClass) plutôt qu'en configurant une vraie connexion PDO pour chaque cas.
 */
class DBIsAbsolutePathTest extends TestCase
{
    /** @dataProvider paths */
    public function test_detects_absolute_paths_on_every_supported_convention(string $path, bool $expected): void
    {
        $method = new \ReflectionMethod(DB::class, 'isAbsolutePath');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke(null, $path));
    }

    /** @return array<string, array{0: string, 1: bool}> */
    public static function paths(): array
    {
        return [
            'relatif simple' => ['storage/database.sqlite', false],
            'relatif avec sous-dossier' => ['storage/app/database.sqlite', false],
            'absolu Unix' => ['/var/www/storage/database.sqlite', true],
            'absolu Windows, antislash' => ['C:\\Users\\Awa\\storage\\database.sqlite', true],
            'absolu Windows, slash' => ['C:/Users/Awa/storage/database.sqlite', true],
            'UNC Windows' => ['\\\\serveur\\partage\\database.sqlite', true],
        ];
    }
}
