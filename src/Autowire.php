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
 */
class Autowire
{
    /**
     * Containers holding dependencies by class name.
     * Must implement PSR-11 Container Interface.
     *
     * @var array<int|string,ContainerInterface>
     */
    private array $containers = [];

    /**
     * @param array<int|string,ContainerInterface> $containers
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
     * Resolve all dependencies for a class using available containers
     * including any containers provided in $extra.
     *
     * @param array<int|string, ContainerInterface> $extra
     *
     * @throws AutowireException
     *
     * @return array<int, null|object>
     */
    public function resolveDependencies(
        string|object $classOrObject,
        string $methodName = '__construct',
        array $extra = [],
    ): array {
        $parameters = [];

        foreach ($this->listDependencies($classOrObject, $methodName) as $dependency) {
            $match = $this->findInContainers(strval($dependency['typeName']), $extra);

            if (null !== $match) {
                $parameters[] = $match;

                continue;
            }

            if (true === $dependency['allowsNull']) {
                $parameters[] = null;

                continue;
            }

            if (true === $dependency['isOptional']) {
                continue;
            }

            throw new AutowireException(sprintf(
                'Could not meet required dependency: %s',
                strval($dependency['typeName'])
            ));
        }

        return $parameters;
    }

    /**
     * Return a new instance of a class after successfully resolving all
     * required dependencies using available containers, including any
     * containers provided in $extra.
     *
     * @param array<int|string, ContainerInterface> $extra
     *
     * @throws AutowireException
     */
    public function instantiateClass(string $class, array $extra = []): object
    {
        return new $class(...$this->resolveDependencies(
            $class,
            '__construct',
            $extra
        ));
    }

    /**
     * List all dependencies (parameters) for a given class or object/callable.
     *
     * @throws AutowireException
     *
     * @return array<int,array<string,mixed>>
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
                throw new AutowireException(
                    'Parameter is not of type ReflectionNamedType'
                );
            }

            $list[] = [
                'typeName' => $parameterTypeName,
                'name' => $parameter->getName(),
                'allowsNull' => $parameter->allowsNull(),
                'isOptional' => $parameter->isOptional(),
            ];
        }

        return $list;
    }

    /**
     * Look for a class in available containers, including any containers
     * provided in $extra.
     *
     * @param array<int|string, ContainerInterface> $extra
     */
    private function findInContainers(string $class, array $extra = []): ?object
    {
        foreach (array_merge($this->containers, $extra) as $container) {
            if ($container->has($class)) {
                $match = $container->get($class);

                if (is_null($match)) {
                    return null;
                }

                if (is_object($match)) {
                    return $match;
                }

                throw new AutowireException(sprintf(
                    'Unknown object type in container for class %s',
                    $class,
                ));
            }
        }

        return null;
    }

    private function classOrObjectHasMethod(
        string|object $classOrObject,
        string $method
    ): bool {
        try {
            $reflectionMethod = new \ReflectionMethod($classOrObject, $method);
        } catch (\ReflectionException) {
            return false;
        }

        return true;
    }

    /**
     * @throws AutowireException
     *
     * @return array<int|string, ReflectionParameter>
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
            $reflectionFunctionOrMethod = $this->reflectMethodFromClassOrObject(
                $classOrObject,
                $method
            );
        }

        return $reflectionFunctionOrMethod->getParameters();
    }

    /**
     * @throws AutowireException
     */
    private function reflectFunctionOrClosure(
        string|Closure $stringOrClosure
    ): ReflectionFunction {
        try {
            return new \ReflectionFunction($stringOrClosure);
        } catch (ReflectionException $e) {
            throw new AutowireException(
                sprintf('Could not reflect callable: %s', $e)
            );
        }
    }

    /**
     * @param string $classOrObject
     *
     * @throws AutowireException
     */
    private function reflectMethodFromClassOrObject(
        string|object $classOrObject,
        string $method
    ): ReflectionMethod {
        try {
            return new \ReflectionMethod($classOrObject, $method);
        } catch (ReflectionException $e) {
            throw new AutowireException(
                sprintf('Could not reflect method: %s', $e)
            );
        }
    }
}
