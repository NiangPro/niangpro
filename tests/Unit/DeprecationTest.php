<?php

namespace Tests\Unit;

use Niang\Core\Application;
use PHPUnit\Framework\TestCase;

class DeprecationTest extends TestCase
{
    public function test_trigger_deprecation_raises_a_silenced_user_deprecation(): void
    {
        $caught = [];
        set_error_handler(function (int $type, string $message) use (&$caught): bool {
            $caught[] = [$type, $message];
            return true;
        });

        try {
            trigger_deprecation('niangpro/framework', '1.6', 'Foo::%s() est déprécié.', 'bar');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([[E_USER_DEPRECATED, 'Since niangpro/framework 1.6: Foo::bar() est déprécié.']], $caught);
    }

    public function test_deprecations_are_logged_once_per_request(): void
    {
        $logFile = base_path('storage/logs/' . date('Y-m-d') . '.log');
        $offset = is_file($logFile) ? (int) filesize($logFile) : 0;
        $message = 'np-deprec-' . uniqid();

        $this->assertTrue(Application::logDeprecation(E_USER_DEPRECATED, $message, 'a.php', 3));
        Application::logDeprecation(E_USER_DEPRECATED, $message, 'a.php', 3);
        clearstatcache();

        $this->assertSame(1, substr_count(substr((string) file_get_contents($logFile), $offset), "Dépréciation : $message"), 'une seule ligne de log');
    }

    public function test_the_stability_document_lists_every_experimental_class(): void
    {
        if (!is_file(base_path('docs/API_STABILITY.md'))) {
            $this->markTestSkipped('Document du dépôt du framework, absent d\'un projet créé.');
        }

        $document = (string) file_get_contents(base_path('docs/API_STABILITY.md'));

        foreach (glob(base_path('packages/*/src/{,*/}*.php'), GLOB_BRACE) ?: [] as $file) {
            if (preg_match('/^ \* @experimental/m', (string) file_get_contents($file)) === 1) {
                $class = basename($file, '.php');
                $this->assertStringContainsString($class, $document, "$class est @experimental mais absent de docs/API_STABILITY.md");
            }
        }
    }
}
