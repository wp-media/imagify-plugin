<?php

declare(strict_types=1);

namespace Imagify\Dependencies\League\Container\Exception;

use Imagify\Dependencies\Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
