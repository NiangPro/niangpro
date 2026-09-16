<?php

namespace Tests\Unit;

use Niang\Core\Container;
use Niang\Core\Exceptions\ContainerException;
use Niang\Core\Exceptions\ContainerNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerTest extends TestCase
{
    public function test_it_autowires_a_class_with_no_dependencies(): void
    {
        $instance = (new Container())->make(ContainerTestLeaf::class);

        $this->assertInstanceOf(ContainerTestLeaf::class, $instance);
    }

    public function test_it_autowires_nested_dependencies(): void
    {
        $instance = (new Container())->make(ContainerTestBranch::class);

        $this->assertInstanceOf(ContainerTestBranch::class, $instance);
        $this->assertInstanceOf(ContainerTestLeaf::class, $instance->leaf);
    }

    public function test_it_throws_a_clear_error_for_a_missing_class(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("[App\\Nope] : cette classe n'existe pas.");

        (new Container())->make('App\\Nope');
    }

    public function test_it_throws_a_clear_error_for_a_non_instantiable_interface(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/pas une classe instanciable/');

        (new Container())->make(ContainerTestInterface::class);
    }

    public function test_it_detects_circular_dependencies_instead_of_crashing(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Dépendance circulaire détectée/');

        (new Container())->make(ContainerTestCircularA::class);
    }

    public function test_it_throws_a_clear_error_for_a_missing_scalar_parameter(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Paramètre manquant : \$name/');

        (new Container())->make(ContainerTestNeedsScalar::class);
    }

    public function test_it_implements_psr11_container_interface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, new Container());
    }

    public function test_get_is_an_alias_of_make(): void
    {
        $container = new Container();

        $this->assertInstanceOf(ContainerTestLeaf::class, $container->get(ContainerTestLeaf::class));
    }

    public function test_has_reflects_bindings_instances_and_existing_classes(): void
    {
        $container = new Container();

        $this->assertTrue($container->has(ContainerTestLeaf::class));
        $this->assertFalse($container->has('App\\Nope'));

        $container->bind('un.alias', fn () => new ContainerTestLeaf());
        $this->assertTrue($container->has('un.alias'));

        $container->singleton('une.instance', new ContainerTestLeaf());
        $this->assertTrue($container->has('une.instance'));
    }

    public function test_get_throws_container_not_found_exception_for_an_unresolvable_id(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectException(ContainerNotFoundException::class);

        (new Container())->get('App\\Nope');
    }

    public function test_container_exception_implements_psr11_exception_interface(): void
    {
        $this->assertInstanceOf(ContainerExceptionInterface::class, new ContainerException('x'));
    }
}

class ContainerTestLeaf
{
}

class ContainerTestBranch
{
    public function __construct(public ContainerTestLeaf $leaf)
    {
    }
}

interface ContainerTestInterface
{
}

class ContainerTestCircularA
{
    public function __construct(public ContainerTestCircularB $b)
    {
    }
}

class ContainerTestCircularB
{
    public function __construct(public ContainerTestCircularA $a)
    {
    }
}

class ContainerTestNeedsScalar
{
    public function __construct(public string $name)
    {
    }
}
