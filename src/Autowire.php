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
     * Returns array of resolved dependencies for a class constructor.
     *
     * Dependencies are resolved from one or more PSR-11 containers.
     *
     * Throws AutowireException if a required dependency could not be met using
     * available containers.
     *
     * @throws AutowireException
     *
     * @return array<null|object>
     */
    public function resolveDependencies(string|object $classOrObject, ContainerInterface ...$containers): array
    {
        $parameters = [];

        if (!method_exists($classOrObject, '__construct')) {
            return $parameters;
        }

        foreach ($this->listDependencies($classOrObject, '__construct') as $dependency) {
            $match = $this->findInContainers((string) $dependency['typeName'], ...$containers);

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
     * required dependencies using provided containers.
     *
     * Throws AutowireException if the class does not exist or if a required
     * dependency could not be met using available containers.
     *
     * @throws AutowireException
     */
    public function instantiateClass(string $class, ContainerInterface ...$containers): object
    {
        if (!class_exists($class)) {
            throw new AutowireException(sprintf(
                'Class does not exist: "%s"',
                $class
            ));
        }

        $dependencies = $this->resolveDependencies(
            $class,
            ...$containers
        );

        return new $class(...$dependencies);
    }

    /**
     * Returns an array of all dependencies (method parameters) and relevant
     * attributes for a given class or object/callable.
     *
     * @throws AutowireException
     *
     * @return array<array<string,bool|string>> Array of parameters with attributes
     */
    public function listDependencies(
        string|object $classOrObject,
        string $methodName = '__construct'
    ): array {
        $list = [];

        if (!method_exists($classOrObject, $methodName)) {
            throw new AutowireException(sprintf(
                'Method does not exist: "%s"',
                $methodName
            ));
        }

        /** @var array<ReflectionParameter> */
        $parameters = $this->reflectParameters($classOrObject, $methodName);

        foreach ($parameters as $parameter) {
            $parameterType = $parameter->getType();

            if (!$parameterType instanceof ReflectionNamedType) {
                throw new AutowireException(
                    'Parameter is not of type ReflectionNamedType'
                );
            }

            $parameterTypeName = $parameterType->getName();

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
     * Look for a class in provided containers.
     */
    private function findInContainers(string $class, ContainerInterface ...$containers): ?object
    {
        foreach ([...$containers] as $container) {
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
            new ReflectionMethod($classOrObject, $method);
        } catch (ReflectionException) {
            return false;
        }

        return true;
    }

    /**
     * Return reflected parameters of specified method from a class or object.
     *
     * @throws AutowireException
     *
     * @return array<ReflectionParameter>
     */
    private function reflectParameters(
        string|object $classOrObject,
        string $method = '__construct'
    ): array {
        if ($classOrObject instanceof Closure) {
            return $this->reflectFunctionOrClosure($classOrObject)->getParameters();
        }

        if (!$this->classOrObjectHasMethod($classOrObject, $method)) {
            throw new AutowireException(sprintf(
                'Method "%s" does not exist in class or object',
                $method
            ));
        }

        return $this->reflectMethodFromClassOrObject(
            $classOrObject,
            $method
        )->getParameters();
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
            return new ReflectionFunction($stringOrClosure);
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
            return new ReflectionMethod($classOrObject, $method);
        } catch (ReflectionException $e) {
            throw new AutowireException(
                sprintf('Could not reflect method: %s', $e)
            );
        }
    }
}
