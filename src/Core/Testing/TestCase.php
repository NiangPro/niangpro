<?php

namespace Niang\Core\Testing;

use Niang\Core\Application;
use Niang\Core\Database\Migrator;
use Niang\Core\Http\Request;
use Niang\Core\Session;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Client de test qui simule des requêtes en dispatchant directement dans le Router,
 * sans passer par un vrai serveur HTTP. Tourne isolé du développement local : APP_ENV=testing
 * (positionné par phpunit.xml) fait charger .env.testing, qui pointe sur une base SQLite en
 * mémoire — jamais storage/database.sqlite.
 */
abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            Session::destroy();
        }

        Session::start();

        $this->app = new Application(base_path());
        $this->app->loadRoutes(base_path('routes/web.php'));

        // Idempotent : ne réapplique que les migrations pas encore jouées dans cette base en mémoire.
        (new Migrator(base_path('database/migrations')))->run();

        if (in_array(RefreshDatabase::class, class_uses($this), true)) {
            $this->refreshDatabase();
        }
    }

    protected function tearDown(): void
    {
        if (in_array(RefreshDatabase::class, class_uses($this), true)) {
            $this->rollbackRefreshedDatabase();
        }

        parent::tearDown();
    }

    /** No-op par défaut, remplacée par RefreshDatabase quand un test l'utilise (`use RefreshDatabase;`). */
    protected function refreshDatabase(): void
    {
    }

    /** @see self::refreshDatabase() */
    protected function rollbackRefreshedDatabase(): void
    {
    }

    protected function get(string $uri): TestResponse
    {
        return $this->call('GET', $uri);
    }

    protected function post(string $uri, array $data = []): TestResponse
    {
        return $this->call('POST', $uri, $data);
    }

    protected function put(string $uri, array $data = []): TestResponse
    {
        return $this->call('PUT', $uri, $data);
    }

    protected function patch(string $uri, array $data = []): TestResponse
    {
        return $this->call('PATCH', $uri, $data);
    }

    protected function delete(string $uri, array $data = []): TestResponse
    {
        return $this->call('DELETE', $uri, $data);
    }

    private function call(string $method, string $uri, array $data = []): TestResponse
    {
        $request = Request::create($method, $uri, $data);
        $response = $this->app->handle($request);

        return new TestResponse($response);
    }
}
