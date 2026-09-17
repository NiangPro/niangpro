<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\Commander;
use PHPUnit\Framework\TestCase;

class CommanderMakeTestTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 3);
    }

    protected function tearDown(): void
    {
        foreach (['SampleTest', 'AlreadyThereTestTest'] as $name) {
            @unlink("{$this->basePath}/tests/Unit/{$name}.php");
        }

        parent::tearDown();
    }

    private function makeTest(?string $name): string
    {
        $commander = new Commander($this->basePath);
        $method = new \ReflectionMethod(Commander::class, 'makeTest');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($commander, $name);

        return ob_get_clean();
    }

    public function test_generates_a_test_appending_the_test_suffix_when_missing(): void
    {
        $output = $this->makeTest('Sample');
        $path = "{$this->basePath}/tests/Unit/SampleTest.php";

        $this->assertFileExists($path);
        $this->assertStringContainsString('Test créé : tests/Unit/SampleTest.php', $output);

        $content = file_get_contents($path);
        $this->assertStringContainsString('namespace Tests\Unit;', $content);
        $this->assertStringContainsString('class SampleTest extends TestCase', $content);
    }

    public function test_does_not_overwrite_an_existing_test(): void
    {
        $path = "{$this->basePath}/tests/Unit/AlreadyThereTestTest.php";
        file_put_contents($path, "<?php\n// contenu personnalisé\n");

        $output = $this->makeTest('AlreadyThereTest');

        $this->assertStringContainsString('existe déjà', $output);
        $this->assertStringContainsString('contenu personnalisé', file_get_contents($path));
    }

    public function test_shows_usage_without_a_name(): void
    {
        $output = $this->makeTest(null);

        $this->assertStringContainsString('Usage : niang make:test NomTest', $output);
    }
}
