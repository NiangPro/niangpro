<?php

namespace Tests\Feature;

use Niang\Core\Application;
use Niang\Core\Event;
use Niang\Core\Events\ApplicationBooted;
use Niang\Core\Events\RequestReceived;
use Niang\Core\Events\ResponsePrepared;
use Niang\Core\Events\RouteMatched;
use Niang\Core\Http\Response;
use Niang\Core\Testing\TestCase;

class FrameworkEventsTest extends TestCase
{
    public function test_request_lifecycle_events_fire_in_order(): void
    {
        $order = [];
        Event::listen(RequestReceived::class, function (RequestReceived $e) use (&$order) {
            $order[] = 'received ' . $e->request->uri;
        });
        Event::listen(RouteMatched::class, function (RouteMatched $e) use (&$order) {
            $order[] = 'matched ' . $e->route['uri'];
        });
        Event::listen(ResponsePrepared::class, function (ResponsePrepared $e) use (&$order) {
            $order[] = 'prepared ' . $e->response->getStatus();
        });
        $this->app->router->get('/fw-events/{id}', fn (string $id) => Response::html("ok $id"));

        $this->get('/fw-events/7')->assertOk();

        $this->assertSame(['received /fw-events/7', 'matched /fw-events/{id}', 'prepared 200'], $order);
    }

    public function test_route_matched_is_not_fired_for_a_404_but_response_prepared_is(): void
    {
        $fired = [];
        Event::listen(RouteMatched::class, function () use (&$fired) {
            $fired[] = 'matched';
        });
        Event::listen(ResponsePrepared::class, function (ResponsePrepared $e) use (&$fired) {
            $fired[] = $e->response->getStatus();
        });

        $this->get('/fw-inexistant')->assertStatus(404);

        $this->assertSame([404], $fired);
    }

    public function test_a_listener_can_modify_the_response(): void
    {
        Event::listen(ResponsePrepared::class, fn (ResponsePrepared $e) => $e->response->header('X-Version', '2.1'));

        $this->assertSame('2.1', $this->get('/health/live')->header('X-Version'));
    }

    public function test_a_failing_listener_gives_an_error_page_not_a_crash(): void
    {
        Event::listen(RequestReceived::class, function () {
            throw new \RuntimeException('écouteur en panne');
        });

        $this->get('/health/live')->assertStatus(500);
    }

    public function test_application_booted_fires_after_providers(): void
    {
        $booted = null;
        Event::listen(ApplicationBooted::class, function (ApplicationBooted $e) use (&$booted) {
            $booted = $e->app;
        });

        $app = new Application(base_path());

        $this->assertSame($app, $booted);
    }

    public function test_request_terminated_fires_after_the_response_is_sent(): void
    {
        $seen = [];
        Event::listen(\Niang\Core\Events\RequestTerminated::class, function (\Niang\Core\Events\RequestTerminated $e) use (&$seen) {
            $seen[] = [ob_get_length() > 0, $e->response->getStatus(), $e->request->uri];
        });
        $server = $_SERVER;
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/health/live', 'HTTP_HOST' => 'localhost'] + $_SERVER;

        try {
            ob_start();
            $this->app->run();
            $output = (string) ob_get_clean();
        } finally {
            $_SERVER = $server;
        }

        $this->assertStringContainsString('"status":"ok"', $output);
        $this->assertSame([[true, 200, '/health/live']], $seen, 'la réponse était déjà écrite quand l\'événement est parti');
    }
}
