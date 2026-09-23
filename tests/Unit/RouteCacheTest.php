<?php

namespace Tests\Unit;

use Niang\Core\RouteCache;
use PHPUnit\Framework\TestCase;

/**
 * Aucun test n'existait sur cette classe avant ce fichier. Vérifie concrètement ce que la
 * docblock de la classe affirme (« les routes à closure ne sont pas sérialisables : elles sont
 * exclues du cache ») plutôt que de le supposer — var_export() ne peut de toute façon pas
 * représenter une Closure (il lèverait une erreur fatale), donc ce n'est un filet de sécurité
 * réel que si le filtrage a bien lieu AVANT l'appel à var_export(), ce que ce test prouve en
 * appelant réellement store() avec un mélange de routes closures et non-closures.
 */
class RouteCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        RouteCache::clear();
        parent::tearDown();
    }

    public function test_closure_routes_are_excluded_and_others_are_cached(): void
    {
        $routes = [
            ['method' => 'GET', 'uri' => '/a', 'action' => [\stdClass::class, 'index'], 'pattern' => '#^/a$#'],
            ['method' => 'GET', 'uri' => '/b', 'action' => fn () => 'closure', 'pattern' => '#^/b$#'],
            ['method' => 'GET', 'uri' => '/c', 'action' => [\stdClass::class, 'show'], 'pattern' => '#^/c$#'],
        ];

        $cached = RouteCache::store($routes, ['a' => '/a', 'c' => '/c']);

        $this->assertSame(2, $cached, 'seules les 2 routes non-closures doivent être comptées');

        $loaded = RouteCache::load();
        $this->assertNotNull($loaded);
        $this->assertCount(2, $loaded['routes']);

        foreach ($loaded['routes'] as $route) {
            $this->assertNotInstanceOf(\Closure::class, $route['action']);
        }

        $uris = array_column($loaded['routes'], 'uri');
        $this->assertSame(['/a', '/c'], $uris);
    }

    public function test_a_closure_fallback_is_dropped_but_a_normal_one_is_kept(): void
    {
        RouteCache::store([], [], fn () => 'jamais sérialisable');
        $this->assertNull(RouteCache::load()['fallback']);

        RouteCache::store([], [], [\stdClass::class, 'notFound']);
        $this->assertSame([\stdClass::class, 'notFound'], RouteCache::load()['fallback']);
    }

    public function test_load_returns_null_when_no_cache_exists(): void
    {
        RouteCache::clear();

        $this->assertFalse(RouteCache::exists());
        $this->assertNull(RouteCache::load());
    }

    public function test_named_routes_round_trip(): void
    {
        RouteCache::store([], ['posts.show' => '/posts/{id}']);

        $this->assertSame(['posts.show' => '/posts/{id}'], RouteCache::load()['named']);
    }

    public function test_clear_removes_the_cache_file(): void
    {
        RouteCache::store([], []);
        $this->assertTrue(RouteCache::exists());

        RouteCache::clear();
        $this->assertFalse(RouteCache::exists());
    }
}
