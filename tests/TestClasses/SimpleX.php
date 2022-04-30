<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test;

class SimpleX
{
    private string $test = 'X';

    public function hello(): string
    {
        return $this->test;
    }
}
