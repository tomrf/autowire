<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test;

class SimpleC
{
    private string $test = 'C';

    public function hello(): string
    {
        return $this->test;
    }
}
