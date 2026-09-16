<?php

namespace Tests\Unit;

use Niang\Core\Container;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Router;
use PHPUnit\Framework\TestCase;

/** Router construit directement (sans passer par l'Application ni routes/web.php) : tests unitaires purs. */
class RouterTest extends TestCase
{
    public function test_match_registers_the_same_action_for_multiple_methods(): void
    {
        $router = new Router();
        $router->match(['GET', 'POST'], '/both', fn (Request $r) => Response::json(['method' => $r->method]));

        $container = new Container();

        $get = $router->dispatch(Request::create('GET', '/both'), $container);
        $post = $router->dispatch(Request::create('POST', '/both'), $container);

        $this->assertSame('{"method":"GET"}', $get->getContent());
        $this->assertSame('{"method":"POST"}', $post->getContent());
    }

    public function test_match_where_applies_the_constraint_to_every_method(): void
    {
        $router = new Router();
        $router->match(['GET', 'POST'], '/item/{id}', fn () => Response::html('ok'))
            ->where(['id' => '[0-9]+']);

        $this->expectException(NotFoundException::class);
        $router->dispatch(Request::create('GET', '/item/abc'), new Container());
    }

    public function test_fallback_is_used_when_nothing_matches(): void
    {
        $router = new Router();
        $router->get('/known', fn () => Response::html('ok'));
        $router->fallback(fn () => Response::json(['message' => 'perdu'], 404));

        $response = $router->dispatch(Request::create('GET', '/inconnu'), new Container());

        $this->assertSame(404, $response->getStatus());
        $this->assertSame('{"message":"perdu"}', $response->getContent());
    }

    public function test_fallback_does_not_override_a_405(): void
    {
        $router = new Router();
        $router->get('/known', fn () => Response::html('ok'));
        $router->fallback(fn () => Response::json(['message' => 'perdu'], 404));

        try {
            $router->dispatch(Request::create('POST', '/known'), new Container());
            $this->fail('Une HttpException 405 était attendue.');
        } catch (HttpException $e) {
            $this->assertSame(405, $e->getStatusCode());
        }
    }

    public function test_head_request_reuses_the_get_route_with_an_empty_body(): void
    {
        $router = new Router();
        $router->get('/page', fn () => Response::html('<h1>Contenu</h1>'));

        $response = $router->dispatch(Request::create('HEAD', '/page'), new Container());

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('', $response->getContent());
    }

    public function test_explicit_head_route_keeps_full_control_of_its_response(): void
    {
        $router = new Router();
        $router->get('/page', fn () => Response::html('<h1>Contenu GET</h1>'));
        $router->head('/page', fn () => Response::html('corps HEAD personnalisé')->header('X-Custom', 'head-route'));

        $response = $router->dispatch(Request::create('HEAD', '/page'), new Container());

        $this->assertSame('head-route', $response->getHeader('X-Custom'));
        $this->assertSame('corps HEAD personnalisé', $response->getContent());
    }
}
