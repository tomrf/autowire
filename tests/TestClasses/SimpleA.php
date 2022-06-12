<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test\TestClasses;

class SimpleA
{
    private string $test = 'A';

    public function hello(): string
    {
        return $this->test;
    }
}
