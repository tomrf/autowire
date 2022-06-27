<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test;

use Tomrf\Autowire\Autowire;
use Tomrf\Autowire\AutowireException;
use Tomrf\Autowire\Test\Container\Container;
use Tomrf\Autowire\Test\TestClasses\DepsA;
use Tomrf\Autowire\Test\TestClasses\DepsAoptsB;
use Tomrf\Autowire\Test\TestClasses\DepsX;
use Tomrf\Autowire\Test\TestClasses\SimpleA;
use Tomrf\Autowire\Test\TestClasses\SimpleB;
use Tomrf\Autowire\Test\TestClasses\SimpleC;

/**
 * @internal
 * @covers \Tomrf\Autowire\Autowire
 * @covers \Tomrf\Autowire\Container
 */
final class AutowireTest extends \PHPUnit\Framework\TestCase
{
    private static Autowire $autowire;

    public static function setUpBeforeClass(): void
    {
        self::$autowire = new Autowire();
    }

    public function testAutowireIsInstanceOfAutowireClass(): void
    {
        static::assertIsObject(self::$autowire);
        static::assertInstanceOf(Autowire::class, self::$autowire);
    }

    public function testInstantiateSimpleClasses(): void
    {
        $a = $this->autowire()->instantiateClass(SimpleA::class);
        $b = $this->autowire()->instantiateClass(SimpleB::class);
        $c = $this->autowire()->instantiateClass(SimpleC::class);

        static::assertInstanceOf(SimpleA::class, $a);
        static::assertInstanceOf(SimpleB::class, $b);
        static::assertInstanceOf(SimpleC::class, $c);
    }

    public function testInstantiateMissingClassFails(): void
    {
        $this->expectException(AutowireException::class);
        $this->autowire()->instantiateClass('DoesNotExist');
    }

    public function testInstantiateClassWithMetRequiredDependency(): void
    {
        $container = new Container();
        $container->set(SimpleA::class, new SimpleA());
        $depsA = $this->autowire()->instantiateClass(DepsA::class, $container);
        static::assertInstanceOf(DepsA::class, $depsA);
    }

    public function testInstantiateClassWithMetRequiredAndOptionalDependency(): void
    {
        $container = new Container();
        $container->set(SimpleA::class, new SimpleA());
        $container->set(SimpleB::class, new SimpleB());

        /** @var DepsAoptsB */
        $depsAoptsB = $this->autowire()->instantiateClass(DepsAoptsB::class, $container);
        static::assertInstanceOf(DepsAoptsB::class, $depsAoptsB);
        static::assertTrue($depsAoptsB->hasDepA());
        static::assertTrue($depsAoptsB->hasDepB());
    }

    public function testInstantiateClassWithUnmetRequiredDependencyFails(): void
    {
        $this->expectException(AutowireException::class);
        $depsX = $this->autowire()->instantiateClass(DepsX::class);
    }

    public function testInstantiateClassWithUnmetOptionalDependency(): void
    {
        $container = new Container();
        $container->set(SimpleA::class, new SimpleA());

        /** @var DepsAoptsB */
        $depsAoptsB = $this->autowire()->instantiateClass(DepsAoptsB::class, $container);

        static::assertInstanceOf(DepsAoptsB::class, $depsAoptsB);
        static::assertTrue($depsAoptsB->hasDepA());
        static::assertFalse($depsAoptsB->hasDepB());
    }

    public function testListDependencies(): void
    {
        $deps = $this->autowire()->listDependencies(DepsAoptsB::class);
        static::assertIsArray($deps);
        static::assertCount(2, $deps);
        static::assertFalse($deps[0]['allowsNull']);
        static::assertFalse($deps[0]['isOptional']);
        static::assertTrue($deps[1]['allowsNull']);
        static::assertFalse($deps[1]['isOptional']);
    }

    public function testListDependenciesForMissingMethodFails(): void
    {
        $this->expectException(AutowireException::class);
        $this->autowire()->listDependencies(SimpleA::class, 'missingMethod');
    }

    private function autowire(): Autowire
    {
        return self::$autowire;
    }
}
