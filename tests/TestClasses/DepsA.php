<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test;

class DepsA
{
    private string $test = 'DepsA';

    public function __construct(
        private SimpleA $depA
    ) {
    }

    public function hello(): string
    {
        return $this->test;
    }
}
