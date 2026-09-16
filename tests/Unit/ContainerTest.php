<?php

namespace Tests\Unit;

use Niang\Core\Container;
use Niang\Core\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;

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
