<?php

namespace Tests\Unit;

use Niang\Core\Container;
use Niang\Core\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;

class ContainerBindingsTest extends TestCase
{
    public function test_bind_an_interface_to_a_class_name(): void
    {
        $container = new Container();
        $container->bind(ContainerBindingsTestGateway::class, ContainerBindingsTestStripe::class);

        $first = $container->make(ContainerBindingsTestGateway::class);
        $this->assertInstanceOf(ContainerBindingsTestStripe::class, $first);
        $this->assertNotSame($first, $container->make(ContainerBindingsTestGateway::class), 'bind() : nouvelle instance à chaque fois');
    }

    public function test_lazy_singletons_by_class_closure_or_self(): void
    {
        $container = new Container();
        ContainerBindingsTestCounter::$built = 0;

        $container->singleton(ContainerBindingsTestCounter::class);
        $this->assertSame(0, ContainerBindingsTestCounter::$built, 'rien n\'est construit avant le premier make()');

        $a = $container->make(ContainerBindingsTestCounter::class);
        $this->assertSame($a, $container->make(ContainerBindingsTestCounter::class));
        $this->assertSame(1, ContainerBindingsTestCounter::$built);

        $container->singleton(ContainerBindingsTestGateway::class, ContainerBindingsTestStripe::class);
        $this->assertSame($container->make(ContainerBindingsTestGateway::class), $container->make(ContainerBindingsTestGateway::class));

        $container->singleton('config.paiement', fn () => new \ArrayObject(['devise' => 'XOF']));
        $this->assertSame($container->make('config.paiement'), $container->make('config.paiement'));
    }

    public function test_instance_and_the_historical_singleton_with_an_object(): void
    {
        $container = new Container();
        $object = new ContainerBindingsTestStripe();

        $container->instance(ContainerBindingsTestGateway::class, $object);
        $this->assertSame($object, $container->make(ContainerBindingsTestGateway::class));

        $other = new ContainerBindingsTestPaypal();
        $container->singleton(ContainerBindingsTestGateway::class, $other);
        $this->assertSame($other, $container->make(ContainerBindingsTestGateway::class));
    }

    public function test_contextual_binding_depends_on_the_consumer(): void
    {
        $container = new Container();
        $container->bind(ContainerBindingsTestGateway::class, ContainerBindingsTestStripe::class);
        $container->when(ContainerBindingsTestRefunds::class)->needs(ContainerBindingsTestGateway::class)->give(ContainerBindingsTestPaypal::class);
        $container->when(ContainerBindingsTestAudit::class)->needs(ContainerBindingsTestGateway::class)->give(fn () => new ContainerBindingsTestPaypal());

        $this->assertInstanceOf(ContainerBindingsTestStripe::class, $container->make(ContainerBindingsTestCheckout::class)->gateway);
        $this->assertInstanceOf(ContainerBindingsTestPaypal::class, $container->make(ContainerBindingsTestRefunds::class)->gateway);
        $this->assertInstanceOf(ContainerBindingsTestPaypal::class, $container->make(ContainerBindingsTestAudit::class)->gateway);
    }

    public function test_contextual_binding_applies_to_method_injection(): void
    {
        $container = new Container();
        $container->bind(ContainerBindingsTestGateway::class, ContainerBindingsTestStripe::class);
        $container->when(ContainerBindingsTestController::class)->needs(ContainerBindingsTestGateway::class)->give(ContainerBindingsTestPaypal::class);

        $result = $container->call([new ContainerBindingsTestController(), 'pay']);

        $this->assertSame(ContainerBindingsTestPaypal::class, $result);
    }

    public function test_give_without_needs_is_an_error(): void
    {
        $this->expectException(ContainerException::class);
        (new Container())->when(ContainerBindingsTestRefunds::class)->give(ContainerBindingsTestPaypal::class);
    }

    public function test_a_resolver_builds_a_whole_family_of_classes(): void
    {
        $container = new Container();
        $container->resolveUsing(\ArrayObject::class, fn (string $class, array $parameters) => new $class(['source' => $parameters['origine'] ?? null]));

        $result = $container->call(fn (ResolvedFamilyMember $member) => $member['source'], ['origine' => 'route']);

        $this->assertSame('route', $result);
    }
}

interface ContainerBindingsTestGateway
{
}

class ContainerBindingsTestStripe implements ContainerBindingsTestGateway
{
}

class ContainerBindingsTestPaypal implements ContainerBindingsTestGateway
{
}

class ContainerBindingsTestCheckout
{
    public function __construct(public ContainerBindingsTestGateway $gateway)
    {
    }
}

class ContainerBindingsTestRefunds extends ContainerBindingsTestCheckout
{
}

class ContainerBindingsTestAudit extends ContainerBindingsTestCheckout
{
}

class ContainerBindingsTestController
{
    public function pay(ContainerBindingsTestGateway $gateway): string
    {
        return $gateway::class;
    }
}

class ContainerBindingsTestCounter
{
    public static int $built = 0;

    public function __construct()
    {
        self::$built++;
    }
}

/** @extends \ArrayObject<string, mixed> */
class ResolvedFamilyMember extends \ArrayObject
{
}
