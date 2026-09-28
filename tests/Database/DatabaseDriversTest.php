<?php

namespace Tests\Database;

use Niang\Core\Cache;
use Niang\Core\Config;
use Niang\Core\Database\DB;
use Niang\Core\DatabaseSessionHandler;
use Niang\Core\Exceptions\ConfigurationException;
use Niang\Core\RateLimiter;
use Niang\Core\Testing\TestCase;

/** SESSION_DRIVER=database et CACHE_DRIVER=database (cache + limitation de débit), contre une vraie base. */
class DatabaseDriversTest extends TestCase
{
    private ?string $previousDriver = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCacheDriver('database');
        DB::statement('DELETE FROM cache_entries');
        DB::statement('DELETE FROM rate_limits');
        DB::statement('DELETE FROM sessions');
    }

    protected function tearDown(): void
    {
        $this->useCacheDriver(null);
        parent::tearDown();
    }

    private function useCacheDriver(?string $driver): void
    {
        if ($driver !== null) {
            $this->previousDriver ??= (string) getenv('CACHE_DRIVER');
            putenv("CACHE_DRIVER=$driver");
        } else {
            $this->previousDriver === '' || $this->previousDriver === null ? putenv('CACHE_DRIVER') : putenv("CACHE_DRIVER={$this->previousDriver}");
        }

        Config::load(base_path());
    }

    public function test_cache_values_round_trip_through_the_database(): void
    {
        Cache::put('stats', ['commandes' => 12, 'total' => 45000.5], 60);

        $this->assertTrue(Cache::has('stats'));
        $this->assertSame(['commandes' => 12, 'total' => 45000.5], Cache::get('stats'));
        $this->assertSame(1, (int) DB::selectOne('SELECT COUNT(*) AS n FROM cache_entries')['n']);
        $this->assertFileDoesNotExist(base_path('storage/framework/cache/' . sha1('stats') . '.cache'));
    }

    public function test_false_null_and_zero_are_real_cached_values(): void
    {
        Cache::put('faux', false);
        Cache::put('zero', 0);

        $this->assertTrue(Cache::has('faux'));
        $this->assertFalse(Cache::get('faux', 'défaut'));
        $this->assertSame(0, Cache::get('zero', 'défaut'));
    }

    public function test_expired_entries_are_ignored_and_removed(): void
    {
        Cache::put('ephemere', 'x', 60);
        DB::statement('UPDATE cache_entries SET expiration = ?', [time() - 1]);

        $this->assertNull(Cache::get('ephemere'));
        $this->assertSame(0, (int) DB::selectOne('SELECT COUNT(*) AS n FROM cache_entries')['n']);
    }

    public function test_put_overwrites_remember_computes_once_forget_and_flush_delete(): void
    {
        Cache::put('cle', 'un');
        Cache::put('cle', 'deux');
        $this->assertSame('deux', Cache::get('cle'));

        $calls = 0;
        $compute = function () use (&$calls) {
            $calls++;
            return 'calculé';
        };
        $this->assertSame('calculé', Cache::remember('lent', 60, $compute));
        $this->assertSame('calculé', Cache::remember('lent', 60, $compute));
        $this->assertSame(1, $calls);

        Cache::forget('cle');
        $this->assertFalse(Cache::has('cle'));

        Cache::flush();
        $this->assertFalse(Cache::has('lent'));
    }

    public function test_long_and_unicode_keys_are_supported(): void
    {
        $key = str_repeat('clé-très-longue/', 40);

        Cache::put($key, 'ok');

        $this->assertSame('ok', Cache::get($key));
    }

    public function test_rate_limiting_is_shared_through_the_database(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue(RateLimiter::attempt('login:1.2.3.4', 3, 60), "tentative $i");
        }

        $this->assertFalse(RateLimiter::attempt('login:1.2.3.4', 3, 60));
        $this->assertTrue(RateLimiter::attempt('login:5.6.7.8', 3, 60), 'une autre IP a son propre compteur');
        $this->assertGreaterThan(55, RateLimiter::availableIn('login:1.2.3.4'));
        $this->assertSame(3, (int) DB::selectOne('SELECT attempts FROM rate_limits WHERE limit_key = ?', [sha1('login:1.2.3.4')])['attempts']);
    }

    public function test_the_rate_limit_window_resets_after_expiry(): void
    {
        RateLimiter::attempt('api', 1, 60);
        $this->assertFalse(RateLimiter::attempt('api', 1, 60));

        DB::statement('UPDATE rate_limits SET reset_at = ?', [time() - 1]);

        $this->assertTrue(RateLimiter::attempt('api', 1, 60));
    }

    public function test_session_handler_stores_reads_and_destroys_sessions(): void
    {
        $handler = new DatabaseSessionHandler(3600);
        $data = 'user_id|i:7;cart|a:1:{i:0;s:4:"thé";}' . "\0binaire";

        $this->assertTrue($handler->write('abc123', $data));
        $this->assertSame($data, $handler->read('abc123'));

        $this->assertTrue($handler->write('abc123', 'mis à jour'));
        $this->assertSame('mis à jour', $handler->read('abc123'));
        $this->assertSame(1, (int) DB::selectOne('SELECT COUNT(*) AS n FROM sessions')['n']);

        // Un « autre serveur » (autre instance) voit la même session.
        $this->assertSame('mis à jour', (new DatabaseSessionHandler(3600))->read('abc123'));

        $handler->destroy('abc123');
        $this->assertSame('', $handler->read('abc123'));
    }

    public function test_expired_sessions_are_not_read_and_are_garbage_collected(): void
    {
        $handler = new DatabaseSessionHandler(3600);
        $handler->write('vieille', 'x');
        $handler->write('recente', 'y');
        DB::statement('UPDATE sessions SET last_activity = ? WHERE id = ?', [time() - 7200, 'vieille']);

        $this->assertSame('', $handler->read('vieille'));
        $this->assertSame(1, $handler->gc(1440));
        $this->assertSame('y', $handler->read('recente'));
    }

    public function test_an_unknown_cache_driver_is_rejected(): void
    {
        $this->useCacheDriver('redis');

        $this->expectException(ConfigurationException::class);
        Cache::get('x');
    }
}
