<?php

declare(strict_types=1);

namespace Tomrf\Autowire;

/**
 * A minimal implementation of a PSR-11 container with a simple set() method.
 */
class Container implements \Psr\Container\ContainerInterface
{
    /**
     * Holds all container items.
     *
     * @var array<string, mixed>
     */
    protected array $container = [];

    /**
     * Gets item from container.
     *
     * Throws NotFoundException if the $id does not exist in the container.
     *
     * @throws NotFoundException
     */
    public function get(string $id): mixed
    {
        if (false === $this->has($id)) {
            throw new NotFoundException('Container does not contain '.$id);
        }

        return $this->container[$id];
    }

    /**
     * Returns true if container has item, false otherwise.
     */
    public function has(string $id): bool
    {
        return isset($this->container[$id]);
    }

    /**
     * Set an item in the container, overwrite any existing item with the same $id.
     */
    public function set(string $id, mixed $value): mixed
    {
        return $this->container[$id] = $value;
    }
}
