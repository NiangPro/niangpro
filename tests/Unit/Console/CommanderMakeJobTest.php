<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\Commander;
use PHPUnit\Framework\TestCase;

class CommanderMakeJobTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 3);
    }

    protected function tearDown(): void
    {
        foreach (['ProcessOrderJob', 'AlreadyThereJob'] as $name) {
            @unlink("{$this->basePath}/app/Jobs/{$name}.php");
        }

        parent::tearDown();
    }

    private function makeJob(?string $name): string
    {
        $commander = new Commander($this->basePath);
        $method = new \ReflectionMethod(Commander::class, 'makeJob');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($commander, $name);

        return ob_get_clean();
    }

    public function test_generates_a_job_appending_the_job_suffix_when_missing(): void
    {
        $output = $this->makeJob('ProcessOrder');
        $path = "{$this->basePath}/app/Jobs/ProcessOrderJob.php";

        $this->assertFileExists($path);
        $this->assertStringContainsString('Job créé : app/Jobs/ProcessOrderJob.php', $output);

        $content = file_get_contents($path);
        $this->assertStringContainsString('namespace App\Jobs;', $content);
        $this->assertStringContainsString('class ProcessOrderJob extends Job', $content);
        $this->assertStringContainsString('public function handle(): void', $content);
    }

    public function test_does_not_overwrite_an_existing_job(): void
    {
        $path = "{$this->basePath}/app/Jobs/AlreadyThereJob.php";
        file_put_contents($path, "<?php\n// contenu personnalisé\n");

        $output = $this->makeJob('AlreadyThereJob');

        $this->assertStringContainsString('existe déjà', $output);
        $this->assertStringContainsString('contenu personnalisé', file_get_contents($path));
    }

    public function test_shows_usage_without_a_name(): void
    {
        $output = $this->makeJob(null);

        $this->assertStringContainsString('Usage : niang make:job NomJob', $output);
    }
}
