<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test\TestClasses;

/**
 * @internal
 */
final class DepsAoptsB
{
    private string $test = 'DepsAoptsB';

    public function __construct(
        private SimpleA $depA,
        private ?SimpleB $depB
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

    public function hasDepB(): bool
    {
        return isset($this->depB);
    }
}
