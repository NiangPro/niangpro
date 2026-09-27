<?php

namespace Tests\Unit;

use Niang\Core\ArraySessionHandler;
use Niang\Core\Cache;
use Niang\Core\Config;
use PHPUnit\Framework\TestCase;

/** Pilote 'array' et increment()/decrement() des pilotes 'array' et 'file' (le pilote 'database' : tests/Database). */
class CacheDriversTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::load(base_path());
    }

    protected function tearDown(): void
    {
        foreach (['array', 'file'] as $driver) {
            Config::set('cache.driver', $driver);
            Cache::flush();
        }

        Config::load(base_path());
        parent::tearDown();
    }

    public function test_array_driver_stores_in_memory_with_ttl(): void
    {
        Config::set('cache.driver', 'array');

        Cache::put('a', ['x' => 1]);
        Cache::put('expiré', 'v', -1);

        $this->assertSame(['x' => 1], Cache::get('a'));
        $this->assertFalse(Cache::has('expiré'));
        $this->assertSame('calculé', Cache::remember('b', 60, fn () => 'calculé'));
        $this->assertSame('calculé', Cache::remember('b', 60, fn () => 'pas rappelé'));

        Cache::forget('a');
        $this->assertNull(Cache::get('a'));

        Cache::flush();
        $this->assertFalse(Cache::has('b'));
        $this->assertSame([], glob(base_path('storage/framework/cache') . '/' . sha1('b') . '.cache') ?: []);
    }

    /** @dataProvider drivers */
    public function test_increment_and_decrement(string $driver): void
    {
        Config::set('cache.driver', $driver);

        $this->assertSame(1, Cache::increment('compteur'));
        $this->assertSame(6, Cache::increment('compteur', 5));
        $this->assertSame(4, Cache::decrement('compteur', 2));
        $this->assertSame(4, Cache::get('compteur'));
        $this->assertSame(-1, Cache::decrement('autre'));
    }

    /** @dataProvider drivers */
    public function test_increment_keeps_the_ttl(string $driver): void
    {
        Config::set('cache.driver', $driver);

        Cache::put('visites', 10, 60);
        Cache::increment('visites');
        Cache::put('expirée', 5, -1);

        $this->assertSame(11, Cache::get('visites'));
        $this->assertSame(1, Cache::increment('expirée'), 'une valeur expirée repart de zéro');
    }

    /** @dataProvider drivers */
    public function test_increment_refuses_a_non_integer_value(string $driver): void
    {
        Config::set('cache.driver', $driver);
        Cache::put('texte', 'abc');

        $this->expectException(\InvalidArgumentException::class);
        Cache::increment('texte');
    }

    /** @return array<string, array{0: string}> */
    public static function drivers(): array
    {
        return ['array' => ['array'], 'file' => ['file']];
    }

    public function test_array_session_handler(): void
    {
        $handler = new ArraySessionHandler();

        $this->assertSame('', $handler->read('id1'));
        $this->assertTrue($handler->write('id1', 'données'));
        $this->assertSame('données', $handler->read('id1'));
        $this->assertTrue($handler->destroy('id1'));
        $this->assertSame('', $handler->read('id1'));
    }
}
