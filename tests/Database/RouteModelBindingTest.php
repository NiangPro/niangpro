<?php

namespace Tests\Database;

use Niang\Core\Container;
use Niang\Core\Database\Model;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;
use Niang\Core\RouteCache;
use Niang\Core\Router;
use Niang\Core\Testing\TestCase;

class RouteModelBindingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('np_test_bound_articles', function ($table) {
            $table->id();
            $table->string('slug')->unique();
            $table->integer('views');
            $table->softDeletes();
        });

        $q = new QueryBuilder('np_test_bound_articles');
        $q->insert(['slug' => 'bonjour', 'views' => 3]);
        (new QueryBuilder('np_test_bound_articles'))->insert(['slug' => 'supprime', 'views' => 0, 'deleted_at' => date('Y-m-d H:i:s')]);
        RouteModelBindingTestGuard::$calls = 0;
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('np_test_bound_articles');
        RouteCache::clear();
        parent::tearDown();
    }

    private function router(): Router
    {
        $router = new Router();
        $router->get('/articles/{article}', [RouteModelBindingTestController::class, 'show'])
            ->bind(['article' => RouteModelBindingTestArticle::class]);
        $router->get('/a/{article}', [RouteModelBindingTestController::class, 'show'])
            ->bind(['article' => RouteModelBindingTestArticle::class . ':slug']);
        $router->get('/garde/{article}', [RouteModelBindingTestController::class, 'show'], [RouteModelBindingTestGuard::class])
            ->bind(['article' => RouteModelBindingTestArticle::class]);

        return $router;
    }

    private function visit(Router $router, string $uri): Response
    {
        try {
            return $router->dispatch(Request::create('GET', $uri), new Container());
        } catch (\Niang\Core\Exceptions\HttpException $e) {
            return Response::html('', $e->getStatusCode());
        }
    }

    public function test_the_parameter_becomes_the_model_row_by_id_or_by_column(): void
    {
        $byId = $this->visit($this->router(), '/articles/1');
        $this->assertSame(['slug' => 'bonjour', 'views' => 3], json_decode($byId->getContent(), true));

        $bySlug = $this->visit($this->router(), '/a/bonjour');
        $this->assertSame('bonjour', json_decode($bySlug->getContent(), true)['slug']);
    }

    public function test_a_missing_or_soft_deleted_row_is_a_404(): void
    {
        $this->assertSame(404, $this->visit($this->router(), '/articles/999')->getStatus());
        $this->assertSame(404, $this->visit($this->router(), '/a/supprime')->getStatus(), 'suppression douce respectée');
        $this->assertSame(404, $this->visit($this->router(), "/a/x' OR '1'='1")->getStatus(), 'valeur liée, jamais interpolée');
    }

    public function test_binding_happens_after_middleware(): void
    {
        $this->assertSame(403, $this->visit($this->router(), '/garde/999')->getStatus(), 'le middleware refuse avant toute recherche');
        $this->assertSame(1, RouteModelBindingTestGuard::$calls);
    }

    public function test_casts_of_the_model_apply(): void
    {
        $this->assertSame(3, json_decode($this->visit($this->router(), '/articles/1')->getContent(), true)['views']);
    }

    public function test_bindings_survive_the_route_cache(): void
    {
        $router = $this->router();
        RouteCache::store($router->routes(), [], null);
        $cached = RouteCache::load();

        $fresh = new Router();
        $fresh->loadFromCache($cached['routes'], $cached['named'], $cached['fallback']);

        $this->assertSame('bonjour', json_decode($this->visit($fresh, '/a/bonjour')->getContent(), true)['slug']);
    }

    public function test_invalid_bindings_are_rejected_at_declaration(): void
    {
        foreach ([
            fn () => (new Router())->get('/x/{article}', fn () => '')->bind(['article' => \stdClass::class]),
            fn () => (new Router())->get('/x/{article}', fn () => '')->bind(['article' => RouteModelBindingTestArticle::class . ':slug; DROP']),
            fn () => (new Router())->get('/x/{id}', fn () => '')->bind(['article' => RouteModelBindingTestArticle::class]),
        ] as $declare) {
            try {
                $declare();
                $this->fail('liaison invalide acceptée');
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}

class RouteModelBindingTestArticle extends Model
{
    protected static string $table = 'np_test_bound_articles';
    protected static bool $timestamps = false;
    protected static bool $softDeletes = true;
    protected static array $casts = ['views' => 'int'];
}

class RouteModelBindingTestController
{
    public function show(array $article): Response
    {
        return Response::json(['slug' => $article['slug'], 'views' => $article['views']]);
    }
}

class RouteModelBindingTestGuard implements Middleware
{
    public static int $calls = 0;

    public function handle(Request $request, \Closure $next): Response
    {
        self::$calls++;
        throw new \Niang\Core\Exceptions\HttpException(403);
    }
}
