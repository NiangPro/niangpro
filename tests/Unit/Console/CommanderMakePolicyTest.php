<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\Commander;
use PHPUnit\Framework\TestCase;

class CommanderMakePolicyTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 3);
    }

    protected function tearDown(): void
    {
        foreach (['CommentPolicy', 'AlreadyTherePolicy'] as $name) {
            @unlink("{$this->basePath}/app/Policies/{$name}.php");
        }

        parent::tearDown();
    }

    private function makePolicy(?string $name): string
    {
        $commander = new Commander($this->basePath);
        $method = new \ReflectionMethod(Commander::class, 'makePolicy');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($commander, $name);

        return ob_get_clean();
    }

    public function test_generates_a_policy_appending_the_policy_suffix_when_missing(): void
    {
        $output = $this->makePolicy('Comment');
        $path = "{$this->basePath}/app/Policies/CommentPolicy.php";

        $this->assertFileExists($path);
        $this->assertStringContainsString('Policy créée : app/Policies/CommentPolicy.php', $output);

        $content = file_get_contents($path);
        $this->assertStringContainsString('namespace App\Policies;', $content);
        $this->assertStringContainsString('class CommentPolicy', $content);
        $this->assertStringContainsString("Gate::policy('prefix', CommentPolicy::class);", $content);
    }

    public function test_does_not_overwrite_an_existing_policy(): void
    {
        $path = "{$this->basePath}/app/Policies/AlreadyTherePolicy.php";
        file_put_contents($path, "<?php\n// contenu personnalisé\n");

        $output = $this->makePolicy('AlreadyTherePolicy');

        $this->assertStringContainsString('existe déjà', $output);
        $this->assertStringContainsString('contenu personnalisé', file_get_contents($path));
    }

    public function test_shows_usage_without_a_name(): void
    {
        $output = $this->makePolicy(null);

        $this->assertStringContainsString('Usage : niang make:policy NomPolicy', $output);
    }
}
