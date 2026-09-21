<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\Commander;
use Niang\Core\Console\SiteTypePrompt;
use PHPUnit\Framework\TestCase;
use Tests\Support\UsesTempDirectory;

/**
 * `niang new` complet clone le projet et lance `composer install` (réseau) : seuls les morceaux
 * qui le composent sont testés ici — l'analyse des arguments, le choix du type, l'installation du
 * thème. resolveSiteType() n'est jamais appelée sans --type ni NIANG_SITE_TYPE : elle poserait une
 * vraie question sur STDIN si les tests tournent dans un terminal.
 */
class CommanderNewTest extends TestCase
{
    use UsesTempDirectory;

    private string $project;

    protected function setUp(): void
    {
        parent::setUp();
        putenv(SiteTypePrompt::ENV_VARIABLE);

        $this->project = $this->makeTempDirectory();
        $this->writeFile($this->project, 'resources/scaffold/themes/vitrine/theme.json', json_encode([
            'label' => 'Site vitrine',
            'next_steps' => ['./bin/niang db:seed'],
        ]));
        $this->writeFile($this->project, 'resources/scaffold/themes/vitrine/routes/web.php', 'routes du thème');
    }

    protected function tearDown(): void
    {
        putenv(SiteTypePrompt::ENV_VARIABLE);
        $this->removeTempDirectories();
        parent::tearDown();
    }

    private function call(string $method, mixed ...$arguments): mixed
    {
        $commander = new Commander($this->project);
        $reflection = new \ReflectionMethod(Commander::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($commander, ...$arguments);
    }

    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();

        return (string) ob_get_clean();
    }

    public function test_arguments_are_parsed_in_any_order(): void
    {
        $this->assertSame(['mon-app', ['type' => 'blog']], $this->call('parseNewArguments', ['mon-app', '--type=blog']));
        $this->assertSame(['mon-app', ['type' => 'blog']], $this->call('parseNewArguments', ['--type=blog', 'mon-app']));
        $this->assertSame(['mon-app', []], $this->call('parseNewArguments', ['mon-app']));
        $this->assertSame([null, []], $this->call('parseNewArguments', []));
    }

    public function test_a_flag_without_value_is_kept_as_null(): void
    {
        $this->assertSame(['mon-app', ['type' => null]], $this->call('parseNewArguments', ['mon-app', '--type']));
    }

    public function test_new_shows_usage_and_the_available_types_without_a_name(): void
    {
        $output = $this->captureOutput(fn () => $this->call('newProject', []));

        $this->assertStringContainsString('Usage : niang new mon-app [--type=<slug>]', $output);
        $this->assertStringContainsString('Types disponibles : vitrine, minimal', $output);
    }

    public function test_new_rejects_an_unknown_option_instead_of_ignoring_a_typo(): void
    {
        $output = $this->captureOutput(fn () => $this->call('newProject', ['mon-app', '--tpye=blog']));

        $this->assertStringContainsString('Usage : niang new', $output);
        $this->assertDirectoryDoesNotExist(dirname($this->project) . '/mon-app');
    }

    public function test_an_explicit_type_is_resolved_without_any_prompt(): void
    {
        $this->assertSame('vitrine', $this->call('resolveSiteType', 'vitrine'));
    }

    public function test_the_environment_variable_is_resolved_without_any_prompt(): void
    {
        putenv(SiteTypePrompt::ENV_VARIABLE . '=vitrine');

        $this->assertSame('vitrine', $this->call('resolveSiteType', null));
    }

    public function test_an_unknown_type_prints_the_valid_ones_and_returns_null(): void
    {
        $result = null;
        $output = $this->captureOutput(function () use (&$result) {
            $result = $this->call('resolveSiteType', 'boutique');
        });

        $this->assertNull($result);
        $this->assertStringContainsString('Type de site inconnu : « boutique »', $output);
        $this->assertStringContainsString('vitrine, minimal', $output);
    }

    public function test_install_site_theme_copies_the_theme_and_returns_the_next_steps(): void
    {
        $target = $this->makeTempDirectory();
        $steps = [];

        $output = $this->captureOutput(function () use ($target, &$steps) {
            $steps = $this->call('installSiteTheme', $target, 'vitrine');
        });

        $this->assertSame('routes du thème', file_get_contents($target . '/routes/web.php'));
        $this->assertSame(['./bin/niang db:seed'], $steps);
        $this->assertStringContainsString('Thème « Site vitrine » installé.', $output);
    }

    public function test_install_site_theme_does_nothing_for_the_minimal_skeleton(): void
    {
        $target = $this->makeTempDirectory();
        $steps = [];

        $output = $this->captureOutput(function () use ($target, &$steps) {
            $steps = $this->call('installSiteTheme', $target, 'minimal');
        });

        $this->assertSame([], $steps);
        $this->assertSame('', $output);
        $this->assertSame(['.', '..'], scandir($target));
    }
}
