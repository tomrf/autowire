<?php

declare(strict_types=1);

namespace Tomrf\Autowire;

/**
 * A minimal implementation of PSR-11 Container Interface including a
 * simple set() method.
 *
 *
 * @package Tomrf\Autowire
 */
class Container implements \Psr\Container\ContainerInterface
{
    /**
     * Holds container items.
     *
     * @var array<string, mixed>
     */
    protected array $container = [];

    /**
     * Get item from container.
     *
     * @param string $id
     * @return mixed
     * @throws NotFoundException
     */
    public function get(string $id): mixed
    {
        if ($this->has($id) === false) {
            throw new NotFoundException('Container does not contain ' . $id);
        }

        return $this->container[$id];
    }

    /**
     * Check if container has item.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->container[$id]);
    }

    /**
     * Set an item in the container, overwrite any existing item
     * with the same ID.
     *
     * @param string $id
     * @param mixed $value
     * @return mixed
     */
    public function set(string $id, mixed $value): mixed
    {
        return $this->container[$id] = $value;
    }
}
