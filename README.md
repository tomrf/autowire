# autowire - autowire dependency injection from PSR-11 containers

[![PHP Version Require](http://poser.pugx.org/tomrf/autowire/require/php?style=flat-square)](https://packagist.org/packages/tomrf/autowire) [![Latest Stable Version](http://poser.pugx.org/tomrf/autowire/v?style=flat-square)](https://packagist.org/packages/tomrf/autowire) [![License](http://poser.pugx.org/tomrf/autowire/license?style=flat-square)](https://packagist.org/packages/tomrf/autowire)

A simple PHP library that facilitates autowired dependency injection by fetching class constructor dependencies from one or more PSR-11 containers.

Autowire uses the PHP reflection API to peek at constructor parameter types and injects required and optional dependencies from assigned containers.

## Installation

Installation via composer:

```bash
composer require tomrf/autowire
```

## Usage

```php
$autowire = new \Tomrf\Autowire\Autowire();
$autowire->addContainer($myDependencyInjectionContainer); // any PSR-11 container

$instance = $autowire->instantiateClass(MyClassWithDependencies::class);
```

## Testing

```bash
composer test
```

## License

This project is released under the MIT License (MIT). See [LICENSE](LICENSE)
for the full license text.


## Documentation

### 📂 Tomrf\Autowire\Autowire::class

Minimal class that facilitates dependency injection by autowiring class constructor dependencies from one or more PSR-11 containers.

#### __construct()





>    **``__construct(array $containers = []): void``**
>
>     @param    \Psr\Container\ContainerInterface[] $containers Array of initial containers
>     @return   void


#### addContainer()

Add a PSR-11 container.



>    **``addContainer(Psr\Container\ContainerInterface $container): void``**
>
>     @return   void


#### resolveDependencies()

Returns array of resolved dependencies for a class constructor or factory method.

Dependencies are reflected from the parameters of $methodName, defaulting
to '__construct'

Additional containers can be used temporarily when resolving dependencies
by passing one or more containers in the optional $extra array.

Throws AutowireException if a required dependency could not be met using
available containers.

>    **``resolveDependencies(object|string $classOrObject, ?string $methodName = __construct, array $extra = []): array``**
>
>     @param    \Psr\Container\ContainerInterface[] $extra Array of extra containers to use during dependency resolution
>     @throws   \Tomrf\Autowire\AutowireException
>     @return   (null|object)[]


#### instantiateClass()

Return a new instance of a class after successfully resolving all required dependencies using available containers, including any containers provided in $extra.

Throws AutowireException if the class does not exist or if a required
dependency could not be met using available containers.

>    **``instantiateClass(string $class, ?string $constructorMethod = __construct, array $extra = []): object``**
>
>     @param    \Psr\Container\ContainerInterface[] $extra Array of extra containers to use during dependency resolution
>     @throws   \Tomrf\Autowire\AutowireException
>     @return   object


#### listDependencies()

Returns an array of all dependencies (method parameters) and relevant attributes for a given class or object/callable.



>    **``listDependencies(object|string $classOrObject, string $methodName = '__construct'): array``**
>
>     @throws   \Tomrf\Autowire\AutowireException
>     @return   array<string,bool|string>[] Array of parameters described as [typeName<string>, name<string>, allowsNull<bool>, isOptional<bool>]




### 📂 Tomrf\Autowire\Container::class

A minimal implementation of a PSR-11 container with a simple set() method.

#### get()

Gets item from container.

Throws NotFoundException if the $id does not exist in the container.

>    **``get(string $id): mixed``**
>
>     @throws   \Tomrf\Autowire\NotFoundException
>     @return   mixed


#### has()

Returns true if container has item, false otherwise.



>    **``has(string $id): bool``**
>
>     @return   bool


#### set()

Set an item in the container, overwrite any existing item with the same $id.



>    **``set(string $id, mixed $value): mixed``**
>
>     @return   mixed




