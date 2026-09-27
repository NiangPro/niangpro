<?php

namespace Tests\Unit;

use Niang\Core\Contracts\ShouldQueue;
use Niang\Core\Event;
use Niang\Core\Events\Dispatcher;
use Niang\Core\Events\StoppableEvent;
use Niang\Core\Queue;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class TypedEventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::reset();
        Queue::reset();
        TypedEventTestListener::$received = [];
    }

    protected function tearDown(): void
    {
        Event::reset();
        Queue::reset();
        parent::tearDown();
    }

    public function test_an_object_is_dispatched_to_listeners_of_its_class(): void
    {
        $seen = [];
        Event::listen(TypedEventTestRegistered::class, function (TypedEventTestRegistered $event) use (&$seen) {
            $seen[] = $event->email;
        });
        Event::listen(TypedEventTestRegistered::class, TypedEventTestListener::class);

        $event = new TypedEventTestRegistered('awa@example.test');
        $returned = Event::dispatch($event);

        $this->assertSame($event, $returned);
        $this->assertSame(['awa@example.test'], $seen);
        $this->assertSame(['awa@example.test'], TypedEventTestListener::$received);
    }

    public function test_listeners_of_parent_classes_and_interfaces_receive_it_after_the_exact_class(): void
    {
        $order = [];
        Event::listen(TypedEventTestAudited::class, function () use (&$order) {
            $order[] = 'interface';
        });
        Event::listen(TypedEventTestBase::class, function () use (&$order) {
            $order[] = 'parent';
        });
        Event::listen(TypedEventTestRegistered::class, function () use (&$order) {
            $order[] = 'classe';
        });

        Event::dispatch(new TypedEventTestRegistered('x'));

        $this->assertSame(['classe', 'parent', 'interface'], $order);
    }

    public function test_listeners_can_modify_the_event(): void
    {
        Event::listen(TypedEventTestRegistered::class, fn (TypedEventTestRegistered $e) => $e->tags[] = 'bienvenue');

        $this->assertSame(['bienvenue'], Event::dispatch(new TypedEventTestRegistered('x'))->tags);
    }

    public function test_a_stoppable_event_stops_propagation(): void
    {
        $calls = [];
        Event::listen(TypedEventTestStoppable::class, function (TypedEventTestStoppable $e) use (&$calls) {
            $calls[] = 1;
            $e->stopPropagation();
        });
        Event::listen(TypedEventTestStoppable::class, function () use (&$calls) {
            $calls[] = 2;
        });

        $event = Event::dispatch(new TypedEventTestStoppable());

        $this->assertSame([1], $calls);
        $this->assertTrue($event->isPropagationStopped());
    }

    public function test_should_queue_listeners_receive_the_object_through_the_queue(): void
    {
        Event::listen(TypedEventTestRegistered::class, TypedEventTestQueuedListener::class);

        Event::dispatch(new TypedEventTestRegistered('file@example.test'));
        $this->assertSame([], TypedEventTestListener::$received);
        $this->assertSame(1, Queue::pending());

        Queue::work();
        $this->assertSame(['file@example.test'], TypedEventTestListener::$received);
    }

    public function test_named_events_still_work(): void
    {
        $received = null;
        Event::listen('user.registered', function (array $user) use (&$received) {
            $received = $user;
        });

        $this->assertNull(Event::dispatch('user.registered', ['id' => 1]));
        $this->assertSame(['id' => 1], $received);
        $this->assertTrue(Event::hasListeners('user.registered'));
        $this->assertFalse(Event::hasListeners('autre'));
    }

    public function test_psr14_dispatcher_uses_the_same_listeners(): void
    {
        Event::listen(TypedEventTestRegistered::class, TypedEventTestListener::class);
        $dispatcher = new Dispatcher();

        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $event = new TypedEventTestRegistered('psr@example.test');
        $this->assertSame($event, $dispatcher->dispatch($event));
        $this->assertSame(['psr@example.test'], TypedEventTestListener::$received);

        $listeners = iterator_to_array($dispatcher->getListenersForEvent($event), false);
        $this->assertCount(1, $listeners);
        $listeners[0](new TypedEventTestRegistered('direct@example.test'));
        $this->assertSame(['psr@example.test', 'direct@example.test'], TypedEventTestListener::$received);
    }

    public function test_the_container_resolves_the_psr14_interface(): void
    {
        $app = new \Niang\Core\Application(base_path());

        $this->assertInstanceOf(Dispatcher::class, $app->container->get(EventDispatcherInterface::class));
    }
}

interface TypedEventTestAudited
{
}

abstract class TypedEventTestBase implements TypedEventTestAudited
{
}

class TypedEventTestRegistered extends TypedEventTestBase
{
    /** @var list<string> */
    public array $tags = [];

    public function __construct(public readonly string $email)
    {
    }
}

class TypedEventTestStoppable extends StoppableEvent
{
}

class TypedEventTestListener
{
    /** @var list<string> */
    public static array $received = [];

    public function handle(TypedEventTestRegistered $event): void
    {
        self::$received[] = $event->email;
    }
}

class TypedEventTestQueuedListener extends TypedEventTestListener implements ShouldQueue
{
}
