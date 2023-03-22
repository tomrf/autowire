<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test\TestClasses;

/**
 * @internal
 */
final class DepsAoptsCustom
{
    private string $test = 'DepsAoptsCustom';

    public function __construct(
        private SimpleA $depA,
        private string $depCustom = SimpleB::class,
    ) {
    }

    public function hello(): string
    {
        return $this->test;
    }

    public function hasDepA(): bool
    {
        return isset($this->depA);
    }

    public function hasDepCustom(): bool
    {
        return isset($this->depCustom);
    }
}
