<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\Commander;
use PHPUnit\Framework\TestCase;

class CommanderMakeEventTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 3);
    }

    protected function tearDown(): void
    {
        foreach (['UserRegisteredEvent', 'AlreadyThereEvent'] as $name) {
            @unlink("{$this->basePath}/app/Events/{$name}.php");
        }

        parent::tearDown();
    }

    private function makeEvent(?string $name): string
    {
        $commander = new Commander($this->basePath);
        $method = new \ReflectionMethod(Commander::class, 'makeEvent');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($commander, $name);

        return ob_get_clean();
    }

    public function test_generates_an_event_appending_the_event_suffix_when_missing(): void
    {
        $output = $this->makeEvent('UserRegistered');
        $path = "{$this->basePath}/app/Events/UserRegisteredEvent.php";

        $this->assertFileExists($path);
        $this->assertStringContainsString('Événement créé : app/Events/UserRegisteredEvent.php', $output);

        $content = file_get_contents($path);
        $this->assertStringContainsString('namespace App\Events;', $content);
        $this->assertStringContainsString('class UserRegisteredEvent', $content);
        $this->assertStringContainsString('Event::dispatch(UserRegisteredEvent::class', $content);
    }

    public function test_does_not_overwrite_an_existing_event(): void
    {
        $path = "{$this->basePath}/app/Events/AlreadyThereEvent.php";
        file_put_contents($path, "<?php\n// contenu personnalisé\n");

        $output = $this->makeEvent('AlreadyThereEvent');

        $this->assertStringContainsString('existe déjà', $output);
        $this->assertStringContainsString('contenu personnalisé', file_get_contents($path));
    }

    public function test_shows_usage_without_a_name(): void
    {
        $output = $this->makeEvent(null);

        $this->assertStringContainsString('Usage : niang make:event NomEvent', $output);
    }
}
