<?php

namespace Niang\Core\Testing;

use Niang\Core\Application;
use Niang\Core\Http\Request;
use Niang\Core\Session;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Client de test qui simule des requêtes en dispatchant directement dans le Router,
 * sans passer par un vrai serveur HTTP.
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
