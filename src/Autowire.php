<?php

declare(strict_types=1);

namespace Tomrf\Autowire;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Minimal library that helps with dependency injection by autowiring
 * from one or more PSR-11 containers.
 *
 * @package Tomrf\Autowire
 */
class Autowire
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
     * class => object provided in $extra.
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
            $parameterType = $parameter->getType();

            if ($parameterType instanceof ReflectionNamedType) {
                $parameterTypeName = $parameterType->getName();
            } else {
                throw new AutowireException('Parameter is not of type ReflectionNamedType');
            }

            $list[] = [
                'typeName' => $parameterTypeName,
                'name' => $parameter->getName(),
                'allowsNull' => $parameter->allowsNull(),
                'isOptional' => $parameter->isOptional()
            ];
        }

        return $list;
    }

    private function classOrObjectHasMethod(string|object $classOrObject, string $method): bool
    {
        try {
            $reflectionMethod = new \ReflectionMethod($classOrObject, $method);
        } catch (\ReflectionException) {
            return false;
        }
        return $reflectionMethod ? true : false;
    }

    /**
     * @param string|object $classOrObject
     * @param string $method
     * @return array<ReflectionParameter>
     * @throws AutowireException
     */
    private function reflectParameters(
        string|object $classOrObject,
        string $method = '__construct'
    ): array {
        if ($classOrObject instanceof Closure) {
            $reflectionFunctionOrMethod = $this->reflectFunctionOrClosure($classOrObject);
        } else {
            /* @todo make this behavior configurable
             *
             * return empty array if the specified method does not exist;
             * this is done to avoid throwing exceptions when instantiating
             * classes without constructor, but should probably not behave
             * like this by default
             *
             */
            if (!$this->classOrObjectHasMethod($classOrObject, $method)) {
                return [];
            }
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
