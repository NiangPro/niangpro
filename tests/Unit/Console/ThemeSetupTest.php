<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\ThemeSetup;
use PHPUnit\Framework\TestCase;
use Tests\Support\UsesTempDirectory;

/** Un faux bin/niang enregistre ses appels : aucun vrai projet n'est migré ici. */
class ThemeSetupTest extends TestCase
{
    use UsesTempDirectory;

    private string $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = $this->makeTempDirectory();
        $this->writeFile($this->project, 'bin/niang', <<<'PHP'
        <?php
        $command = implode(' ', array_slice($argv, 1));
        file_put_contents(dirname(__DIR__) . '/calls.log', getcwd() . '|' . $command . "\n", FILE_APPEND);
        echo "Exécuté : $command\n";
        exit($argv[1] === 'boom' ? 3 : 0);
        PHP);
    }

    protected function tearDown(): void
    {
        $this->removeTempDirectories();
        parent::tearDown();
    }

    /** @return array{0: list<string>, 1: string} */
    private function runSetup(array $commands): array
    {
        $output = '';
        $done = (new ThemeSetup($this->project))->run($commands, function (string $text) use (&$output): void {
            $output .= $text;
        });

        return [$done, $output];
    }

    private function calls(): array
    {
        return file($this->project . '/calls.log', FILE_IGNORE_NEW_LINES) ?: [];
    }

    public function test_commands_run_in_order_inside_the_new_project_with_their_arguments(): void
    {
        [$done, $output] = $this->runSetup(['migrate', 'db:seed AdminUser']);

        $this->assertSame(['migrate', 'db:seed AdminUser'], $done);
        $this->assertSame([realpath($this->project) . '|migrate', realpath($this->project) . '|db:seed AdminUser'], $this->calls());
        $this->assertStringContainsString('Exécuté : db:seed AdminUser', $output);
    }

    public function test_the_first_failure_stops_the_remaining_commands(): void
    {
        [$done, $output] = $this->runSetup(['migrate', 'boom', 'db:seed']);

        $this->assertSame(['migrate'], $done);
        $this->assertCount(2, $this->calls());
        $this->assertStringContainsString('« niang boom » a échoué (code 3)', $output);
    }
}
