# autowire - autowire dependency injection from PSR-11 containers

[![PHP Version Require](http://poser.pugx.org/tomrf/autowire/require/php?style=flat-square)](https://packagist.org/packages/tomrf/autowire) [![Latest Stable Version](http://poser.pugx.org/tomrf/autowire/v?style=flat-square)](https://packagist.org/packages/tomrf/autowire) [![License](http://poser.pugx.org/tomrf/autowire/license?style=flat-square)](https://packagist.org/packages/tomrf/autowire)

A simple PHP library that facilitates autowired dependency injection by fetching class constructor dependencies from one or more PSR-11 containers.

Autowire uses the PHP reflection API to peek at constructor parameter types and injects required and optional dependencies from assigned containers.

📔 [Go to documentation](#documentation)

## Installation
Installation via composer:

```bash
composer require tomrf/autowire
```

## Usage
```php
$autowire = new \Tomrf\Autowire\Autowire();

$instance = $autowire->instantiateClass(MyClassWithDependencies::class, $container);
```

## Testing
```bash
composer test
```

## License
This project is released under the MIT License (MIT).
See [LICENSE](LICENSE) for more information.

## Documentation
 - [Tomrf\Autowire\Autowire](#-tomrfautowireautowireclass)
   - [resolveDependencies](#resolvedependencies)
   - [instantiateClass](#instantiateclass)
   - [listDependencies](#listdependencies)


***

### 📂 Tomrf\Autowire\Autowire::class

Minimal class that facilitates dependency injection by autowiring
class constructor dependencies from one or more PSR-11 containers.

#### resolveDependencies()

Returns array of resolved dependencies for a class constructor.

Dependencies are resolved from one or more PSR-11 containers.

Throws AutowireException if a required dependency could not be met using
available containers.

```php
public function resolveDependencies(
    object|string $classOrObject,
    Psr\Container\ContainerInterface $containers
): array

@throws   \Tomrf\Autowire\AutowireException
@return   (null|object)[]
```

#### instantiateClass()

Return a new instance of a class after successfully resolving all
required dependencies using provided containers.

Throws AutowireException if the class does not exist or if a required
dependency could not be met using available containers.

```php
public function instantiateClass(
    string $class,
    Psr\Container\ContainerInterface $containers
): object

@throws   \Tomrf\Autowire\AutowireException
```

#### listDependencies()

Returns an array of all dependencies (method parameters) and relevant
attributes for a given class or object/callable.

```php
public function listDependencies(
    object|string $classOrObject,
    string $methodName = '__construct'
): array

@throws   \Tomrf\Autowire\AutowireException
@return   array<string,bool|string>[] Array of parameters with attributes
```



***

_Generated 2022-06-28T01:41:09+02:00 using 📚[tomrf/readme-gen](https://packagist.org/packages/tomrf/readme-gen)_
