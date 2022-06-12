<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test\TestClasses;

/**
 * @internal
 */
final class SimpleX
{
    private string $test = 'X';

    public function hello(): string
    {
        return $this->test;
    }
}
