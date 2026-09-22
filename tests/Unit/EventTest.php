<?php

namespace Tests\Unit;

use Niang\Core\Contracts\ShouldQueue;
use Niang\Core\Event;
use Niang\Core\Queue;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::reset();
        Queue::reset();
        EventTestSyncListener::$calls = [];
        EventTestQueuedListener::$calls = [];
    }

    protected function tearDown(): void
    {
        Event::reset();
        Queue::reset();
        parent::tearDown();
    }

    public function test_a_closure_listener_runs_synchronously(): void
    {
        $called = false;
        Event::listen('demo', function (string $value) use (&$called): void {
            $called = true;
            $this->assertSame('awa', $value);
        });

        Event::dispatch('demo', 'awa');

        $this->assertTrue($called);
        $this->assertSame(0, Queue::pending());
    }

    public function test_a_class_listener_without_shouldqueue_runs_synchronously(): void
    {
        Event::listen('demo', EventTestSyncListener::class);

        Event::dispatch('demo', 'awa');

        $this->assertSame(['awa'], EventTestSyncListener::$calls);
        $this->assertSame(0, Queue::pending());
    }

    public function test_a_shouldqueue_class_listener_is_deferred_instead_of_run_immediately(): void
    {
        Event::listen('demo', EventTestQueuedListener::class);

        Event::dispatch('demo', 'awa');

        // Pas exécuté tout de suite : poussé sur la file (CallQueuedListener).
        $this->assertSame([], EventTestQueuedListener::$calls);
        $this->assertSame(1, Queue::pending());

        Queue::work();

        $this->assertSame(['awa'], EventTestQueuedListener::$calls);
    }

    public function test_reset_clears_registered_listeners(): void
    {
        Event::listen('demo', EventTestSyncListener::class);
        Event::reset();

        Event::dispatch('demo', 'awa');

        $this->assertSame([], EventTestSyncListener::$calls);
    }
}

class EventTestSyncListener
{
    public static array $calls = [];

    public function handle(string $value): void
    {
        self::$calls[] = $value;
    }
}

class EventTestQueuedListener implements ShouldQueue
{
    public static array $calls = [];

    public function handle(string $value): void
    {
        self::$calls[] = $value;
    }
}
