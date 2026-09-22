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

    /**
     * Aucune route existante n'a de segment dynamique en première position ({slug}) : ce cas n'a
     * jusqu'ici jamais été exercé. L'index par premier segment statique (voir Router::buildIndex())
     * doit toujours les considérer candidates de toute requête, faute de quoi elles cesseraient de
     * matcher silencieusement.
     */
    public function test_a_route_with_a_dynamic_first_segment_still_matches(): void
    {
        $router = new Router();
        $router->get('/{slug}', fn (Request $r) => Response::json(['slug' => $r->param('slug')]));

        $response = $router->dispatch(Request::create('GET', '/a-propos'), new Container());

        $this->assertSame('{"slug":"a-propos"}', $response->getContent());
    }

    /** L'index par premier segment ne doit jamais faire "fuir" une route vers le mauvais segment. */
    public function test_routes_with_different_first_segments_do_not_interfere(): void
    {
        $router = new Router();
        $router->get('/posts', fn () => Response::html('posts'));
        $router->get('/tags', fn () => Response::html('tags'));

        $this->assertSame('posts', $router->dispatch(Request::create('GET', '/posts'), new Container())->getContent());
        $this->assertSame('tags', $router->dispatch(Request::create('GET', '/tags'), new Container())->getContent());
    }

    /** Ajouter une route après un premier dispatch (index déjà construit) doit invalider l'index. */
    public function test_a_route_added_after_the_index_was_built_is_still_matched(): void
    {
        $router = new Router();
        $router->get('/first', fn () => Response::html('first'));
        $router->dispatch(Request::create('GET', '/first'), new Container()); // construit l'index

        $router->get('/second', fn () => Response::html('second'));

        $response = $router->dispatch(Request::create('GET', '/second'), new Container());

        $this->assertSame('second', $response->getContent());
    }
}
