<?php

namespace Tests\Unit\Redis;

use Niang\Core\Cache;
use Niang\Core\Config;
use Niang\Core\Exceptions\RedisException;
use Niang\Core\Job;
use Niang\Core\Queue;
use Niang\Core\RateLimiter;
use Niang\Core\Redis;
use Niang\Core\RedisSessionHandler;
use PHPUnit\Framework\TestCase;

/**
 * Pilotes 'redis' contre un vrai serveur :
 *   NP_REDIS_HOST=127.0.0.1 NP_REDIS_PORT=6390 NP_REDIS_PASSWORD=... vendor/bin/phpunit tests/Unit/Redis
 * Ignoré sans NP_REDIS_HOST. Chaque exécution utilise son propre préfixe de clés.
 */
class RedisDriversTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $host = getenv('NP_REDIS_HOST');

        if ($host === false || $host === '') {
            $this->markTestSkipped('Pas de serveur Redis (NP_REDIS_HOST).');
        }

        Config::load(base_path());
        Config::set('redis', [
            'host' => $host,
            'port' => (int) (getenv('NP_REDIS_PORT') ?: 6379),
            'password' => (string) getenv('NP_REDIS_PASSWORD'),
            'username' => '',
            'database' => 1,
            'prefix' => 'nptest:' . uniqid() . ':',
            'timeout' => 2,
        ]);
        Config::set('cache.driver', 'redis');
        Config::set('queue.driver', 'redis');
        Redis::reset();
        RedisDriversTestJob::$handled = [];
    }

    protected function tearDown(): void
    {
        if (getenv('NP_REDIS_HOST')) {
            Cache::flush();
            Queue::reset();
            Redis::reset();
        }

        Config::load(base_path());
        parent::tearDown();
    }

    public function test_the_connection_is_binary_safe_and_reports_errors(): void
    {
        $key = Redis::connection()->key('brut');
        Redis::command('SET', $key, "a\r\nb\0c");

        $this->assertSame("a\r\nb\0c", Redis::command('GET', $key));
        $this->assertNull(Redis::command('GET', Redis::connection()->key('absente')));

        try {
            $this->expectException(RedisException::class);
            Redis::command('INCR', $key);
        } finally {
            Redis::command('DEL', $key);
        }
    }

    public function test_cache_values_ttl_and_forget(): void
    {
        Cache::put('a', ['x' => 1, 'faux' => false]);
        Cache::put('faux', false);
        Cache::put('court', 'v', 1);
        Cache::put('expiré', 'v', 0);

        $this->assertSame(['x' => 1, 'faux' => false], Cache::get('a'));
        $this->assertTrue(Cache::has('faux'));
        $this->assertFalse(Cache::get('faux', 'défaut'));
        $this->assertFalse(Cache::has('expiré'));
        $this->assertSame(1, Redis::command('TTL', Redis::connection()->key('cache:' . sha1('court'))));
        $this->assertSame('calculé', Cache::remember('r', 60, fn () => 'calculé'));

        Cache::forget('a');
        $this->assertNull(Cache::get('a'));
    }

    public function test_flush_only_removes_this_applications_cache(): void
    {
        $foreign = 'autre-application:cache:x';
        Redis::command('SET', $foreign, 'à garder');
        Cache::put('a', 1);

        Cache::flush();

        $this->assertNull(Cache::get('a'));
        $this->assertSame('à garder', Redis::command('GET', $foreign));
        Redis::command('DEL', $foreign);
    }

    public function test_increment_is_atomic_keeps_the_ttl_and_refuses_non_integers(): void
    {
        $this->assertSame(1, Cache::increment('n'));
        $this->assertSame(6, Cache::increment('n', 5));
        $this->assertSame(4, Cache::decrement('n', 2));
        $this->assertSame(4, Cache::get('n'), 'relisible par get() : même format que put()');

        Cache::put('visites', 10, 60);
        Cache::increment('visites');
        $this->assertSame(11, Cache::get('visites'));
        $this->assertGreaterThan(0, Redis::command('TTL', Redis::connection()->key('cache:' . sha1('visites'))));

        Cache::put('texte', 'abc');
        $this->expectException(\InvalidArgumentException::class);
        Cache::increment('texte');
    }

    public function test_rate_limiter(): void
    {
        $key = 'login|' . uniqid();

        $this->assertTrue(RateLimiter::attempt($key, 2, 60));
        $this->assertTrue(RateLimiter::attempt($key, 2, 60));
        $this->assertFalse(RateLimiter::attempt($key, 2, 60));
        $this->assertGreaterThan(55, RateLimiter::availableIn($key));

        RateLimiter::clear($key);
        $this->assertTrue(RateLimiter::attempt($key, 2, 60));
        RateLimiter::clear($key);
    }

    public function test_session_handler(): void
    {
        $handler = new RedisSessionHandler(120);

        $this->assertSame('', $handler->read('s1'));
        $this->assertTrue($handler->write('s1', 'données'));
        $this->assertSame('données', $handler->read('s1'));
        $this->assertGreaterThan(100, Redis::command('TTL', Redis::connection()->key('session:s1')));
        $this->assertTrue($handler->destroy('s1'));
        $this->assertSame('', $handler->read('s1'));
    }

    public function test_queue_push_work_later_and_named_queues(): void
    {
        Queue::push(new RedisDriversTestJob('a'));
        Queue::push(new RedisDriversTestJob('emails'), 'emails');
        Queue::later(3600, new RedisDriversTestJob('plus-tard'));

        $this->assertSame(3, Queue::pending());
        $this->assertSame(1, Queue::work('emails'));
        $this->assertSame(['emails'], RedisDriversTestJob::$handled);

        $this->assertSame(1, Queue::work());
        $this->assertSame(['emails', 'a'], RedisDriversTestJob::$handled);
        $this->assertSame(1, Queue::pending(), 'le job différé attend');
    }

    public function test_queue_failures_retry_and_flush(): void
    {
        $id = Queue::push(new RedisDriversTestJob('boum', fail: true, tries: 2));

        Queue::work();
        $this->assertCount(0, Queue::failed(), 'une tentative reste : remis en attente avec backoff');
        $this->assertSame(1, Queue::pending());

        // Tentative suivante disponible tout de suite : on avance le score du job différé.
        Redis::command('ZADD', Redis::connection()->key('queues:default:delayed'), time() - 1, $id);
        Queue::work();

        $failed = Queue::failed();
        $this->assertCount(1, $failed);
        $this->assertSame([$id, RedisDriversTestJob::class, 'boum'], [$failed[0]['id'], $failed[0]['class'], $failed[0]['error']]);
        $this->assertSame(0, Queue::pending());

        $this->assertSame(1, Queue::flush(), 'avant retry : un job échoué à purger');
        $this->assertSame([], Queue::failed());

        $id = Queue::push(new RedisDriversTestJob('encore', fail: true, tries: 1));
        Queue::work();
        $this->assertTrue(Queue::retry($id));
        $this->assertFalse(Queue::retry('inconnu'));
        $this->assertSame([], Queue::failed(), 'retiré des échecs');
        $this->assertSame(1, Queue::pending(), 'remis en file, tentatives remises à zéro');
    }

    public function test_a_reservation_left_by_a_crashed_worker_is_released_after_retry_after(): void
    {
        Config::set('queue.retry_after', 60);
        $id = Queue::push(new RedisDriversTestJob('repris'));

        // Un worker a réservé le job puis s'est arrêté, il y a plus de retry_after.
        Redis::command('LPOP', Redis::connection()->key('queues:default'));
        Redis::command('ZADD', Redis::connection()->key('queues:default:reserved'), time() - 1, $id);

        $this->assertSame(1, Queue::work());
        $this->assertSame(['repris'], RedisDriversTestJob::$handled);
    }
}

class RedisDriversTestJob extends Job
{
    /** @var list<string> */
    public static array $handled = [];

    public function __construct(private string $name, private bool $fail = false, int $tries = 1)
    {
        $this->tries = $tries;
    }

    public function handle(): void
    {
        if ($this->fail) {
            throw new \RuntimeException($this->name);
        }

        self::$handled[] = $this->name;
    }
}
