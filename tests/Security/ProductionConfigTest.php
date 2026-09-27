<?php

namespace Tests\Security;

use Niang\Core\Application;
use Niang\Core\Http\Response;
use Niang\Core\Testing\TestCase;

/** Roadmap §8 : en production, debug jamais actif et refus de servir sans APP_KEY valide. */
class ProductionConfigTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $saved = [];

    private function env(string $key, ?string $value): void
    {
        $this->saved[$key] ??= getenv($key);
        $value === null ? putenv($key) : putenv("$key=$value");
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $key => $value) {
            $value === false ? putenv($key) : putenv("$key=$value");
        }

        parent::tearDown();
    }

    public function test_debug_is_always_off_in_production(): void
    {
        $this->env('APP_ENV', 'production');

        $this->env('APP_DEBUG', 'true');
        $this->assertFalse(Application::debug(), 'APP_DEBUG=true ignoré en production');

        $this->env('APP_DEBUG', null);
        $this->assertFalse(Application::debug(), 'APP_DEBUG absent : désactivé en production');
    }

    public function test_debug_defaults_to_on_outside_production(): void
    {
        $this->env('APP_ENV', 'local');
        $this->env('APP_DEBUG', null);
        $this->assertTrue(Application::debug());

        $this->env('APP_DEBUG', 'false');
        $this->assertFalse(Application::debug());
    }

    public function test_errors_never_leak_details_in_production(): void
    {
        $this->env('APP_ENV', 'production');
        $this->env('APP_DEBUG', 'true');
        $this->app->router->get('/sec-boum', function (): Response {
            throw new \RuntimeException('mot de passe SQL : hunter2 dans /var/www/secret.php');
        });

        $response = $this->get('/sec-boum');

        $response->assertStatus(500);
        $response->assertDontSee('hunter2');
        $response->assertDontSee('secret.php');
        $response->assertDontSee('RuntimeException');
    }

    public function test_the_debug_toolbar_never_shows_in_production(): void
    {
        $this->env('APP_ENV', 'local');
        $this->env('APP_DEBUG', 'true');
        $this->get('/')->assertSee('requête(s) SQL');

        $this->env('APP_ENV', 'production');
        $this->get('/')->assertDontSee('requête(s) SQL');
    }

    public function test_production_refuses_to_serve_without_a_valid_app_key(): void
    {
        $this->env('APP_ENV', 'production');

        $this->env('APP_KEY', '');
        $this->assertNotSame([], Application::productionProblems());

        $this->env('APP_KEY', 'trop-courte');
        $this->assertNotSame([], Application::productionProblems());

        $this->env('APP_KEY', str_repeat('a', 64));
        $this->assertSame([], Application::productionProblems());
    }

    public function test_run_answers_503_without_details_when_misconfigured(): void
    {
        $this->env('APP_ENV', 'production');
        $this->env('APP_KEY', '');

        ob_start();
        $this->app->run();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('503', $output);
        $this->assertStringNotContainsString('APP_KEY', $output, 'le détail va dans les logs, pas au visiteur');
    }

    public function test_nothing_is_checked_outside_production(): void
    {
        $this->env('APP_ENV', 'local');
        $this->env('APP_KEY', '');

        $this->assertSame([], Application::productionProblems());
    }
}
