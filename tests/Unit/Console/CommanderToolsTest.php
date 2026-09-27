<?php

namespace Tests\Unit\Console;

use Niang\Core\Config;
use Niang\Core\ConfigCache;
use Niang\Core\Console\Commander;
use Niang\Core\RouteCache;
use PHPUnit\Framework\TestCase;

/** about, env, optimize:clear, cors:check, make:notification. */
class CommanderToolsTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 3);
    }

    protected function tearDown(): void
    {
        @unlink("{$this->basePath}/app/Notifications/CommandeExpedieeNotification.php");
        @rmdir("{$this->basePath}/app/Notifications");
        RouteCache::clear();
        ConfigCache::clear();
        Config::load($this->basePath);
        parent::tearDown();
    }

    /** @var array<string, mixed> appliqués après la construction du Commander (qui recharge config/) */
    private array $config = [];

    private function command(string $method, mixed ...$args): string
    {
        $commander = new Commander($this->basePath);

        foreach ($this->config as $key => $value) {
            Config::set($key, $value);
        }

        $reflection = new \ReflectionMethod(Commander::class, $method);

        ob_start();
        $reflection->invoke($commander, ...$args);

        return (string) ob_get_clean();
    }

    public function test_about_lists_versions_drivers_and_caches(): void
    {
        $output = $this->command('about');

        foreach (['Application', 'PHP', PHP_VERSION, 'Pilotes', 'Base de données', 'File d\'attente', 'Caches', 'Routes'] as $expected) {
            $this->assertStringContainsString($expected, $output);
        }
    }

    public function test_env_prints_the_environment(): void
    {
        $this->assertMatchesRegularExpression('/^Environnement : \w+ \(/', $this->command('environment'));
    }

    public function test_optimize_clear_removes_route_and_config_caches(): void
    {
        RouteCache::store([], []);
        ConfigCache::store(['app' => []]);
        $this->assertTrue(RouteCache::exists());

        $output = $this->command('optimizeClear');

        $this->assertFalse(RouteCache::exists());
        $this->assertFalse(ConfigCache::exists());
        $this->assertStringContainsString('cache:clear', $output);
    }

    public function test_cors_check_flags_credentials_with_wildcard_and_simulates_a_preflight(): void
    {
        $this->config = ['cors.allowed_origins' => ['*'], 'cors.supports_credentials' => true, 'cors.allowed_methods' => ['GET', 'POST']];

        $output = $this->command('corsCheck', 'https://app.example.test');

        $this->assertStringContainsString('⚠ Cookies autorisés', $output);
        $this->assertStringContainsString('⚠ Méthode OPTIONS (préflight) absente', $output);
        $this->assertStringContainsString('aucune origine n\'est alors acceptée', $output);
        $this->assertStringContainsString('Préflight depuis https://app.example.test : refusé', $output, '« * » + cookies : Cors refuse tout');
        $this->assertStringContainsString('point(s) à vérifier', $output);
    }

    public function test_cors_check_reports_a_refused_origin_and_a_path_in_an_origin(): void
    {
        $this->config = ['cors.allowed_origins' => ['https://app.example.test/']];

        $output = $this->command('corsCheck', 'https://autre.test');

        $this->assertStringContainsString('contient un chemin', $output);
        $this->assertStringContainsString('refusé', $output);
    }

    public function test_cors_check_shows_the_headers_of_an_accepted_origin(): void
    {
        $this->config = ['cors.allowed_origins' => ['https://app.example.test'], 'cors.supports_credentials' => true];

        $output = $this->command('corsCheck', 'https://app.example.test');

        $this->assertStringContainsString('accepté', $output);
        $this->assertStringContainsString('Access-Control-Allow-Origin: https://app.example.test', $output);
        $this->assertStringContainsString('Access-Control-Allow-Credentials: true', $output);
    }

    public function test_make_notification_generates_valid_php(): void
    {
        $output = $this->command('makeNotification', 'CommandeExpediee');
        $path = "{$this->basePath}/app/Notifications/CommandeExpedieeNotification.php";

        $this->assertStringContainsString('Notification créée', $output);
        $this->assertFileExists($path);
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path), $lint, $code);
        $this->assertSame(0, $code, implode("\n", $lint));
        $this->assertStringContainsString('extends Notification', (string) file_get_contents($path));
        $this->assertStringContainsString('existe déjà', $this->command('makeNotification', 'CommandeExpediee'));
    }
}
