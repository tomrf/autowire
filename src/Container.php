<?php

declare(strict_types=1);

namespace Tomrf\Autowire;

class Container implements \Psr\Container\ContainerInterface
{
    protected array $container = [];

    public function get(string $id): mixed
    {
        if ($this->has($id) === false) {
            throw new NotFoundException('Container does not contain ' . $id);
        }

        return $this->container[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->container[$id]);
    }

    public function set(string $id, mixed $value): mixed
    {
        return $this->container[$id] = $value;
    }
}
