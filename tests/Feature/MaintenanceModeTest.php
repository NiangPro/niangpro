<?php

namespace Tests\Feature;

use Niang\Core\Console\Commander;
use Niang\Core\Cookie;
use Niang\Core\MaintenanceMode;
use Niang\Core\Testing\TestCase;

class MaintenanceModeTest extends TestCase
{
    protected function tearDown(): void
    {
        MaintenanceMode::deactivate();
        unset($_COOKIE['niang_maintenance']);
        parent::tearDown();
    }

    public function test_the_site_answers_normally_when_not_down(): void
    {
        $this->assertFalse(MaintenanceMode::isDown());
        $this->get('/')->assertOk();
    }

    public function test_every_page_answers_503_while_down(): void
    {
        MaintenanceMode::activate();

        $this->get('/')->assertStatus(503)->assertSee('503');
        $this->get('/page-qui-n-existe-pas')->assertStatus(503);
        $this->post('/contact', ['name' => 'x'])->assertStatus(503);
    }

    public function test_the_maintenance_page_is_translated_and_standalone(): void
    {
        MaintenanceMode::activate();

        $response = $this->get('/');

        $response->assertSee(__('http.page_maintenance'));
        $this->assertStringContainsString('noindex', $response->content());
    }

    public function test_retry_after_is_sent_when_requested(): void
    {
        MaintenanceMode::activate(120);

        $this->assertSame('120', $this->get('/')->header('Retry-After'));
    }

    public function test_json_clients_get_a_json_503(): void
    {
        MaintenanceMode::activate();

        $response = $this->get('/', ['Accept' => 'application/json']);

        $response->assertStatus(503);
        $this->assertSame(__('http.503'), $response->json()['message']);
    }

    public function test_health_endpoints_stay_up_for_monitoring(): void
    {
        MaintenanceMode::activate();

        $this->get('/up')->assertOk();
        $this->get('/health')->assertOk();
    }

    public function test_the_secret_url_sets_a_bypass_cookie_and_redirects_home(): void
    {
        MaintenanceMode::activate(null, 'passe-droit');

        $response = $this->get('/passe-droit');

        $response->assertRedirect('/');
        $response->assertCookie('niang_maintenance');
        $this->assertStringNotContainsString('passe-droit', (string) $response->cookie('niang_maintenance'), 'le secret en clair ne doit pas finir dans un cookie');
    }

    public function test_a_visitor_with_the_bypass_cookie_browses_normally(): void
    {
        MaintenanceMode::activate(null, 'passe-droit');
        $cookie = $this->get('/passe-droit')->cookie('niang_maintenance');

        $_COOKIE['niang_maintenance'] = $this->encodeCookie('niang_maintenance', (string) $cookie);

        $this->get('/')->assertOk();
    }

    public function test_a_wrong_secret_or_forged_cookie_is_refused(): void
    {
        MaintenanceMode::activate(null, 'passe-droit');

        $this->get('/autre-chose')->assertStatus(503);

        $_COOKIE['niang_maintenance'] = $this->encodeCookie('niang_maintenance', hash('sha256', 'mauvais'));
        $this->get('/')->assertStatus(503);

        // Un cookie non chiffré par l'application (fabriqué par le navigateur) est ignoré.
        $_COOKIE['niang_maintenance'] = hash('sha256', 'passe-droit');
        $this->get('/')->assertStatus(503);
    }

    public function test_the_secret_is_never_written_in_clear_on_disk(): void
    {
        MaintenanceMode::activate(null, 'passe-droit');

        $this->assertStringNotContainsString('passe-droit', (string) file_get_contents(base_path('storage/framework/down')));
    }

    public function test_down_and_up_commands(): void
    {
        $commander = new Commander(base_path());

        $output = $this->runCommand($commander, 'down', [['--retry=30', '--secret']]);

        $this->assertTrue(MaintenanceMode::isDown());
        $this->assertSame(30, MaintenanceMode::data()['retry']);
        $this->assertMatchesRegularExpression('#/([0-9a-f]{32}) \(cookie#', $output);

        preg_match('#/([0-9a-f]{32}) \(cookie#', $output, $m);
        $this->get('/' . ($m[1] ?? ''))->assertRedirect('/');

        $this->assertStringContainsString('Site rouvert', $this->runCommand($commander, 'up'));
        $this->assertFalse(MaintenanceMode::isDown());
        $this->get('/')->assertOk();
    }

    private function runCommand(Commander $commander, string $method, array $args = []): string
    {
        $reflection = new \ReflectionMethod(Commander::class, $method);
        $reflection->setAccessible(true);

        ob_start();
        $reflection->invoke($commander, ...$args);

        return (string) ob_get_clean();
    }

    private function encodeCookie(string $name, string $value): string
    {
        $encode = new \ReflectionMethod(Cookie::class, 'encode');
        $encode->setAccessible(true);

        return $encode->invoke(null, $name, $value, 60);
    }
}
