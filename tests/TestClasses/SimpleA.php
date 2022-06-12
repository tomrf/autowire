<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test\TestClasses;

/**
 * @internal
 */
final class SimpleA
{
    private string $test = 'A';

    public function hello(): string
    {
        return $this->test;
    }
}
