<?php

declare(strict_types=1);

namespace Tomrf\Autowire\Test\Container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException.
 *
 * @internal
 */
final class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
