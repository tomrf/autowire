<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test\TestClasses;

/**
 * @internal
 */
final class SimpleB
{
    private string $test = 'B';

    public function hello(): string
    {
        return $this->test;
    }
}
