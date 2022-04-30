<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test;

class DepsX
{
    private string $test = 'DepsA';

    public function __construct(
        private SimpleX $depX
    ) {
    }

    public function hello(): string
    {
        return $this->test;
    }
}
