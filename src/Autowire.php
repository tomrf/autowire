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
 * Minimal class that facilitates dependency injection by autowiring
 * class constructor dependencies from one or more PSR-11 containers.
 *
 * @api
 */
class Autowire
{
    /**
     * Containers holding dependencies by class name.
     *
     * Must implement PSR-11 ContainerInterface.
     *
     * @var array<ContainerInterface>
     */
    private array $containers = [];

    /**
     * @param array<ContainerInterface> $containers Array of initial containers
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
     * Returns array of resolved dependencies for a class constructor or factory
     * method.
     *
     * Dependencies are reflected from the parameters of $methodName, defaulting
     * to '__construct'
     *
     * Additional containers can be used temporarily when resolving dependencies
     * by passing one or more containers in the optional $extra array.
     *
     * Throws AutowireException if a required dependency could not be met using
     * available containers.
     *
     * @param array<ContainerInterface> $extra Array of extra containers to use
     *                                         during dependency resolution
     *
     * @throws AutowireException
     *
     * @return array<null|object>
     */
    public function resolveDependencies(
        string|object $classOrObject,
        ?string $methodName = '__construct',
        array $extra = [],
    ): array {
        $parameters = [];

        if (null === $methodName) {
            return [];
        }

        foreach ($this->listDependencies($classOrObject, $methodName) as $dependency) {
            $match = $this->findInContainers((string) ($dependency['typeName']), $extra);

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
                'Could not meet required dependency "%s"',
                (string) ($dependency['typeName'])
            ));
        }

        return $parameters;
    }

    /**
     * Return a new instance of a class after successfully resolving all
     * required dependencies using available containers, including any
     * containers provided in $extra.
     *
     * Throws AutowireException if the class does not exist or if a required
     * dependency could not be met using available containers.
     *
     * @param array<ContainerInterface> $extra Array of extra containers to use
     *                                         during dependency resolution
     *
     * @throws AutowireException
     */
    public function instantiateClass(
        string $class,
        ?string $constructorMethod = '__construct',
        array $extra = []
    ): object {
        if (!class_exists($class)) {
            throw new AutowireException(sprintf(
                'Class does not exist: "%s"',
                $class
            ));
        }

        if (null === $constructorMethod) {
            return new $class();
        }

        $dependencies = $this->resolveDependencies(
            $class,
            $this->classOrObjectHasMethod($class, $constructorMethod)
                ? $constructorMethod
                : null,
            $extra
        );

        return new $class(...$dependencies);
    }

    /**
     * Returns an array of all dependencies (method parameters) and relevant
     * attributes for a given class or object/callable.
     *
     * @throws AutowireException
     *
     * @return array<array<string,bool|string>> Array of parameters described as
     *                                          [typeName<string>, name<string>,
     *                                          allowsNull<bool>, isOptional<bool>]
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

                if (null === $match) {
                    return null;
                }

                if (\is_object($match)) {
                    return $match;
                }

                throw new AutowireException(sprintf(
                    'Unknown object type in container for class "%s"',
                    $class,
                ));
            }
        }

        return null;
    }

    /**
     * Returns true if the given class or object contains the method specified,
     * false otherwise.
     */
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
     * Return reflected parameters of specified method from a class or object.
     *
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
            if (!$this->classOrObjectHasMethod($classOrObject, $method)) {
                throw new AutowireException(sprintf(
                    'Method "%s" does not exist in class or object',
                    $method
                ));
            }

            $reflectionFunctionOrMethod = $this->reflectMethodFromClassOrObject(
                $classOrObject,
                $method
            );
        }

        return $reflectionFunctionOrMethod->getParameters();
    }

    /**
     * Reflect a function or a Closure.
     *
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
     * Reflect a method from a class or an object.
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
