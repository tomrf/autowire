<?php

declare(strict_types=1);

namespace Tomrf\Autowire;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Minimal library that helps with dependency injection autowiring
 * from one or more PSR-11 containers.
 *
 * @package Tomrf\Autowire
 */
final class Autowire
{
    /**
     * Containers holding dependencies by class name.
     * Must implement PSR-11 Container Interface.
     *
     * @var array<\Psr\Container\ContainerInterface>
     */
    private array $containers = [];

    /**
     * @param array<\Psr\Container\ContainerInterface> $containers
     * @return void
     */
    public function __construct(array $containers = [])
    {
        foreach ($containers as $container) {
            $this->addContainer($container);
        }
    }

    /**
     * Add a PSR-11 container.
     */
    public function addContainer(ContainerInterface $container): void
    {
        $this->containers[] = $container;
    }

    /**
     * Resolve all dependencies for a class using available containers and any
     * extra service provided in $extra.
     *
     * @param string|object $classOrObject
     * @param string $methodName
     * @param array<string, object> $extra
     * @return array<int, object|null>
     * @throws AutowireException
     */
    public function resolveDependencies(
        string|object $classOrObject,
        string $methodName = '__construct',
        array $extra = [],
    ): array {
        $parameters = [];
        $dependencies = $this->listDependencies($classOrObject, $methodName);

        foreach ($dependencies as $dependency) {
            $match = $this->findInContainers($dependency['typeName'], $extra);

            if ($match !== null) {
                $parameters[] = $match;
                continue;
            }

            if ($dependency['allowsNull'] === true) {
                $parameters[] = null;
                continue;
            }

            if ($dependency['isOptional'] === true) {
                continue;
            }

            throw new AutowireException('Could not meet required dependency: ' . $dependency['typeName']);
        }

        return $parameters;
    }

    /**
     * Return a new instance of a class after successfully resolving all
     * required dependencies using available containers.
     *
     * @param string $class
     * @param array<string, object> $extra
     * @return object
     * @throws AutowireException
     */
    public function instantiateClass(string $class, array $extra = []): object
    {
        return new $class(...$this->resolveDependencies($class, '__construct', $extra));
    }

    /**
     * Look for a class in available containers, including any
     * class => object provided in the $extra array.
     *
     * @todo @improve $extra is a bit messy -- make it containers
     *
     * @param string $class
     * @param array<string, object> $extra
     * @return null|object
     */
    private function findInContainers(string $class, array $extra = []): ?object
    {
        if (isset($extra[$class])) {
            return $extra[$class];
        }

        foreach ($this->containers as $container) {
            if ($container->has($class)) {
                return $container->get($class);
            }
        }

        return null;
    }

    /**
     * List all dependencies (parameters) for a given class or object/callable.
     *
     * @param string|object $classOrObject
     * @param string $methodName
     * @return array<array>
     * @throws AutowireException
     */
    public function listDependencies(
        string|object $classOrObject,
        string $methodName = '__construct'
    ): array {
        $list = [];

        /** @var array<ReflectionParameter> */
        $parameters = $this->reflectParameters($classOrObject, $methodName);
        foreach ($parameters as $parameter) {
            $list[] = [
                'name' => $parameter->getName(),
                'typeName' => $parameter->getType()->getName(),
                'allowsNull' => $parameter->allowsNull(),
                'isOptional' => $parameter->isOptional()
            ];
        }

        return $list;
    }

    /**
     * @param string|object $classOrObject
     * @param string $method
     * @return array<ReflectionParameter>
     * @throws AutowireException
     */
    private function reflectParameters(/* @todo handle non-callable object with method */
        string|object $classOrObject,
        string $method = '__construct'
    ): array {

        if ($classOrObject instanceof Closure) {
            $reflectionFunctionOrMethod = $this->reflectFunctionOrClosure($classOrObject);
        } else {
            $reflectionFunctionOrMethod = $this->reflectMethodFromClassOrObject($classOrObject, $method);
        }

        return $reflectionFunctionOrMethod->getParameters();
    }

    /**
     * @param string|Closure $stringOrClosure
     * @return ReflectionFunction
     * @throws AutowireException
     */
    private function reflectFunctionOrClosure(string|Closure $stringOrClosure): ReflectionFunction
    {
        try {
            return new \ReflectionFunction($stringOrClosure);
        } catch (ReflectionException $e) {
            throw new AutowireException('Could not reflect callable: ' . $e);
        }
    }

    /**
     * @param string $classOrObject
     * @param string $method
     * @return ReflectionMethod
     * @throws AutowireException
     */
    private function reflectMethodFromClassOrObject(string|object $classOrObject, string $method): ReflectionMethod
    {
        try {
            return new \ReflectionMethod($classOrObject, $method);
        } catch (ReflectionException $e) {
            throw new AutowireException('Could not reflect method: ' . $e);
        }
    }
}
