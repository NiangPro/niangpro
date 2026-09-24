<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\ComposerHooks;
use Niang\Core\Console\ProjectScaffolder;
use Niang\Core\Console\SiteTypePrompt;
use PHPUnit\Framework\TestCase;
use Tests\Support\UsesTempDirectory;

/**
 * Composer n'est pas installé dans ce projet : l'événement et son IO sont simulés avec les seules
 * méthodes que le hook utilise (getIO, isInteractive, ask, write, writeError).
 */
class ComposerHooksTest extends TestCase
{
    use UsesTempDirectory;

    private ProjectScaffolder $scaffolder;
    private string $project;

    protected function setUp(): void
    {
        parent::setUp();
        putenv(SiteTypePrompt::ENV_VARIABLE);

        $scaffold = $this->makeTempDirectory();
        $this->writeFile($scaffold, 'themes/vitrine/theme.json', json_encode([
            'label' => 'Site vitrine',
            'order' => 10,
            'next_steps' => ['./bin/niang serve'],
            'notes' => ['Compte admin de test : admin@example.com'],
        ]));
        $this->writeFile($scaffold, 'themes/vitrine/routes/web.php', 'routes du thème');

        $this->scaffolder = new ProjectScaffolder($scaffold);
        $this->project = $this->makeTempDirectory();
        $this->writeFile($this->project, 'routes/web.php', 'routes de démo');
    }

    protected function tearDown(): void
    {
        putenv(SiteTypePrompt::ENV_VARIABLE);
        $this->removeTempDirectories();
        parent::tearDown();
    }

    /** @param list<string> $answers */
    private function event(bool $interactive, array $answers = [], bool $abortOnAsk = false): object
    {
        $io = new class ($interactive, $answers, $abortOnAsk) {
            /** @var list<string> */
            public array $written = [];
            /** @var list<string> */
            public array $errors = [];
            public int $asked = 0;

            /** @param list<string> $answers */
            public function __construct(private bool $interactive, private array $answers, private bool $abortOnAsk)
            {
            }

            public function isInteractive(): bool
            {
                return $this->interactive;
            }

            public function ask(string $question): ?string
            {
                $this->asked++;

                if ($this->abortOnAsk) {
                    throw new \RuntimeException('Aborted.'); // ce que fait Composer sur une fin de saisie (Ctrl+D)
                }

                return array_shift($this->answers);
            }

            public function write(string $message, bool $newline = true): void
            {
                $this->written[] = $message;
            }

            public function writeError(string $message): void
            {
                $this->errors[] = $message;
            }
        };

        return new class ($io) {
            public function __construct(public object $io)
            {
            }

            public function getIO(): object
            {
                return $this->io;
            }
        };
    }

    private function routes(): string
    {
        return (string) file_get_contents($this->project . '/routes/web.php');
    }

    public function test_no_interaction_keeps_the_minimal_skeleton_without_asking(): void
    {
        $event = $this->event(false);

        ComposerHooks::handle($event, $this->project, true, $this->scaffolder);

        $this->assertSame(0, $event->io->asked);
        $this->assertSame('routes de démo', $this->routes());
    }

    public function test_a_stdin_that_is_not_a_tty_never_prompts_even_if_composer_says_interactive(): void
    {
        $event = $this->event(true, ['1']);

        ComposerHooks::handle($event, $this->project, false, $this->scaffolder);

        $this->assertSame(0, $event->io->asked);
        $this->assertSame('routes de démo', $this->routes());
    }

    public function test_the_environment_variable_installs_the_theme_without_a_prompt(): void
    {
        putenv(SiteTypePrompt::ENV_VARIABLE . '=vitrine');
        $event = $this->event(false);

        ComposerHooks::handle($event, $this->project, false, $this->scaffolder);

        $this->assertSame(0, $event->io->asked);
        $this->assertSame('routes du thème', $this->routes());
        $this->assertStringContainsString('Thème « Site vitrine » installé', implode("\n", $event->io->written));
        $this->assertStringContainsString('./bin/niang serve', implode("\n", $event->io->written));
        $this->assertStringContainsString('Compte admin de test : admin@example.com', implode("\n", $event->io->written));
    }

    public function test_an_interactive_answer_installs_the_chosen_theme(): void
    {
        $event = $this->event(true, ['1']);

        ComposerHooks::handle($event, $this->project, true, $this->scaffolder);

        $this->assertSame(1, $event->io->asked);
        $this->assertSame('routes du thème', $this->routes());
    }

    public function test_end_of_input_during_the_question_keeps_the_minimal_skeleton_instead_of_failing(): void
    {
        $event = $this->event(true, [], abortOnAsk: true);

        ComposerHooks::handle($event, $this->project, true, $this->scaffolder);

        $this->assertSame(1, $event->io->asked);
        $this->assertSame('routes de démo', $this->routes());
        $this->assertSame([], $event->io->errors);
    }

    public function test_choosing_minimal_interactively_changes_nothing(): void
    {
        $event = $this->event(true, ['2']);

        ComposerHooks::handle($event, $this->project, true, $this->scaffolder);

        $this->assertSame('routes de démo', $this->routes());
        $this->assertStringNotContainsString('installé', implode("\n", $event->io->written));
    }

    public function test_a_typo_in_the_environment_variable_warns_and_keeps_the_project_instead_of_failing(): void
    {
        putenv(SiteTypePrompt::ENV_VARIABLE . '=vitrne');
        $event = $this->event(false);

        ComposerHooks::handle($event, $this->project, false, $this->scaffolder);

        $this->assertSame('routes de démo', $this->routes());
        $this->assertCount(1, $event->io->errors);
        $this->assertStringContainsString('vitrne', $event->io->errors[0]);
        $this->assertStringContainsString('vitrine', $event->io->errors[0]);
    }

    public function test_the_composer_json_script_points_to_an_existing_static_method(): void
    {
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 3) . '/composer.json'), true);
        $callable = $composer['scripts']['post-create-project-cmd'];

        [$class, $method] = explode('::', $callable);

        $this->assertSame(ComposerHooks::class, $class);
        $this->assertTrue((new \ReflectionMethod($class, $method))->isStatic());
    }

    public function test_it_creates_the_env_file_with_a_unique_app_key(): void
    {
        $this->writeFile($this->project, '.env.example', "APP_NAME=NiangPro\nAPP_KEY=\nDB_CONNECTION=sqlite\n");

        ComposerHooks::handle($this->event(false), $this->project, false, $this->scaffolder);

        $env = (string) file_get_contents($this->project . '/.env');
        $this->assertStringContainsString("APP_NAME=NiangPro\n", $env);
        $this->assertMatchesRegularExpression('/^APP_KEY=[0-9a-f]{64}$/m', $env);

        $other = $this->makeTempDirectory();
        $this->writeFile($other, '.env.example', "APP_KEY=\n");
        $this->writeFile($other, 'routes/web.php', '');
        ComposerHooks::handle($this->event(false), $other, false, $this->scaffolder);
        $this->assertNotSame($env, file_get_contents($other . '/.env'), 'Chaque projet doit avoir sa propre clé.');
    }

    public function test_it_never_overwrites_an_existing_env_file(): void
    {
        $this->writeFile($this->project, '.env.example', "APP_KEY=\n");
        $this->writeFile($this->project, '.env', "APP_KEY=cle-existante\n");

        ComposerHooks::handle($this->event(false), $this->project, false, $this->scaffolder);

        $this->assertSame("APP_KEY=cle-existante\n", file_get_contents($this->project . '/.env'));
    }
}
