<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\Commander;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie que Commander découvre et exécute une vraie commande custom (app/Console/Commands/)
 * via son Command::$signature — pas seulement que make:command génère un fichier.
 */
class CommanderCustomCommandTest extends TestCase
{
    private string $basePath;
    private string $commandPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 3);
        $this->commandPath = "{$this->basePath}/app/Console/Commands/PingCustomCommand.php";

        $dir = dirname($this->commandPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->commandPath, <<<'PHP'
        <?php

        namespace App\Console\Commands;

        use Niang\Core\Console\Command;

        class PingCustomCommand extends Command
        {
            public static string $signature = 'ping:custom';
            public static string $description = 'Commande de test';

            public function handle(array $arguments): void
            {
                echo 'pong:' . implode(',', $arguments);
            }
        }
        PHP);
    }

    protected function tearDown(): void
    {
        @unlink($this->commandPath);
        parent::tearDown();
    }

    private function runCustomCommand(string $command, array $arguments): array
    {
        $commander = new Commander($this->basePath);
        $method = new \ReflectionMethod(Commander::class, 'runCustomCommand');
        $method->setAccessible(true);

        ob_start();
        $found = $method->invoke($commander, $command, $arguments);

        return [$found, ob_get_clean()];
    }

    public function test_runs_the_matching_custom_command_with_its_arguments(): void
    {
        [$found, $output] = $this->runCustomCommand('ping:custom', ['a', 'b']);

        $this->assertTrue($found);
        $this->assertSame('pong:a,b', $output);
    }

    public function test_returns_false_when_no_signature_matches(): void
    {
        [$found, $output] = $this->runCustomCommand('does:not-exist', []);

        $this->assertFalse($found);
        $this->assertSame('', $output);
    }
}
