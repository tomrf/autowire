<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test\TestClasses;

/**
 * @internal
 */
final class DepsX
{
    private string $test = 'DepsX';

    public function __construct(
        private SimpleX $depX
    ) {
    }

    public function hello(): string
    {
        return $this->test;
    }
}
