<?php

declare(strict_types=1);

use Tomrf\Autowire\Autowire;

require 'vendor/autoload.php';

$autowire = new Autowire();
var_dump($autowire->listDependencies(\Tomrf\Autowire\Test\TestClasses\DepsAoptsB::class));
