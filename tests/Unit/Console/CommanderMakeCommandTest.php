<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\Commander;
use PHPUnit\Framework\TestCase;

class CommanderMakeCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 3);
    }

    protected function tearDown(): void
    {
        foreach (['ReportDailyCommand', 'AlreadyThereCommand'] as $name) {
            @unlink("{$this->basePath}/app/Console/Commands/{$name}.php");
        }

        parent::tearDown();
    }

    private function makeCommand(?string $name): string
    {
        $commander = new Commander($this->basePath);
        $method = new \ReflectionMethod(Commander::class, 'makeCommand');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($commander, $name);

        return ob_get_clean();
    }

    public function test_generates_a_command_appending_the_command_suffix_when_missing(): void
    {
        $output = $this->makeCommand('ReportDaily');
        $path = "{$this->basePath}/app/Console/Commands/ReportDailyCommand.php";

        $this->assertFileExists($path);
        $this->assertStringContainsString('Commande créée : app/Console/Commands/ReportDailyCommand.php', $output);

        $content = file_get_contents($path);
        $this->assertStringContainsString('namespace App\Console\Commands;', $content);
        $this->assertStringContainsString('class ReportDailyCommand extends Command', $content);
        $this->assertStringContainsString("public static string \$signature = 'mon:nom';", $content);
    }

    public function test_does_not_overwrite_an_existing_command(): void
    {
        $path = "{$this->basePath}/app/Console/Commands/AlreadyThereCommand.php";
        file_put_contents($path, "<?php\n// contenu personnalisé\n");

        $output = $this->makeCommand('AlreadyThereCommand');

        $this->assertStringContainsString('existe déjà', $output);
        $this->assertStringContainsString('contenu personnalisé', file_get_contents($path));
    }

    public function test_shows_usage_without_a_name(): void
    {
        $output = $this->makeCommand(null);

        $this->assertStringContainsString('Usage : niang make:command NomCommand', $output);
    }
}
