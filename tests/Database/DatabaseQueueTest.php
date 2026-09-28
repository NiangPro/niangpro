<?php

namespace Tests\Database;

use Niang\Core\Config;
use Niang\Core\Database\DB;
use Niang\Core\Exceptions\ConfigurationException;
use Niang\Core\Job;
use Niang\Core\Queue;
use Niang\Core\Testing\TestCase;

/** QUEUE_DRIVER=database contre une vraie base (SQLite en local, MySQL et PostgreSQL en CI). */
class DatabaseQueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('queue.driver', 'database');
        Queue::reset();
        DatabaseQueueTestJob::$handled = [];
    }

    protected function tearDown(): void
    {
        Config::set('queue.driver', 'database');
        Queue::reset();
        Config::load(base_path());
        parent::tearDown();
    }

    public function test_push_stores_a_row_and_work_runs_then_deletes_it(): void
    {
        $id = Queue::push(new DatabaseQueueTestJob('a'));

        $this->assertSame(1, Queue::pending());
        $row = DB::selectOne('SELECT * FROM jobs WHERE job_id = ?', [$id]);
        $this->assertSame('default', $row['queue']);
        $this->assertStringNotContainsString("\0", $row['payload'], 'base64 : pas d\'octet nul (PostgreSQL)');

        $this->assertSame(1, Queue::work());
        $this->assertSame(['a'], DatabaseQueueTestJob::$handled);
        $this->assertSame(0, Queue::pending());
    }

    public function test_later_and_named_queues(): void
    {
        Queue::later(3600, new DatabaseQueueTestJob('plus-tard'));
        Queue::push(new DatabaseQueueTestJob('emails'), 'emails');
        Queue::push(new DatabaseQueueTestJob('défaut'));

        $this->assertSame(1, Queue::work('emails'));
        $this->assertSame(['emails'], DatabaseQueueTestJob::$handled);

        $this->assertSame(1, Queue::work());
        $this->assertSame(['emails', 'défaut'], DatabaseQueueTestJob::$handled);
        $this->assertSame(1, Queue::pending(), 'le job différé attend');
    }

    public function test_a_job_reserved_by_another_worker_is_skipped_until_it_is_stale(): void
    {
        $id = Queue::push(new DatabaseQueueTestJob('x'));
        DB::statement('UPDATE jobs SET reserved_at = ? WHERE job_id = ?', [time(), $id]);

        $this->assertSame(0, Queue::work(), 'un autre worker l\'a réservé');

        // Worker arrêté depuis plus de retry_after : le job est rendu à la file.
        DB::statement('UPDATE jobs SET reserved_at = ? WHERE job_id = ?', [time() - 601, $id]);
        $this->assertSame(1, Queue::work());
        $this->assertSame(['x'], DatabaseQueueTestJob::$handled);
    }

    public function test_failures_are_retried_with_backoff_then_moved_to_failed_jobs(): void
    {
        $id = Queue::push(new DatabaseQueueTestJob('échec', fail: true, tries: 2));

        $this->assertSame(0, Queue::work());
        $row = DB::selectOne('SELECT * FROM jobs WHERE job_id = ?', [$id]);
        $this->assertSame(1, (int) $row['attempts']);
        $this->assertNull($row['reserved_at']);
        $this->assertGreaterThan(time(), (int) $row['available_at'], 'backoff avant la tentative suivante');

        DB::statement('UPDATE jobs SET available_at = ? WHERE job_id = ?', [time(), $id]);
        Queue::work();

        $this->assertSame(0, Queue::pending());
        $failed = Queue::failed();
        $this->assertCount(1, $failed);
        $this->assertSame($id, $failed[0]['id']);
        $this->assertSame(DatabaseQueueTestJob::class, $failed[0]['class']);
        $this->assertSame('boum', $failed[0]['error']);
    }

    public function test_retry_and_flush_failed_jobs(): void
    {
        $id = Queue::push(new DatabaseQueueTestJob('échec', fail: true, tries: 1));
        Queue::work();
        $this->assertCount(1, Queue::failed());

        $this->assertTrue(Queue::retry($id));
        $this->assertFalse(Queue::retry('inconnu'));
        $this->assertSame([], Queue::failed());
        $this->assertSame(1, Queue::pending());

        Queue::work();
        $this->assertSame(1, Queue::flush());
        $this->assertSame([], Queue::failed());
    }

    public function test_sync_driver_runs_immediately(): void
    {
        Config::set('queue.driver', 'sync');

        Queue::push(new DatabaseQueueTestJob('tout-de-suite'));

        $this->assertSame(['tout-de-suite'], DatabaseQueueTestJob::$handled);
    }

    public function test_an_unknown_driver_is_rejected(): void
    {
        Config::set('queue.driver', 'redis-mal-tapé');

        $this->expectException(ConfigurationException::class);
        Queue::push(new DatabaseQueueTestJob('x'));
    }
}

class DatabaseQueueTestJob extends Job
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
            throw new \RuntimeException('boum');
        }

        self::$handled[] = $this->name;
    }
}
