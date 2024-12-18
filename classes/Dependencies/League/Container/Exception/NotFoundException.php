<?php

declare(strict_types=1);

namespace Imagify\Dependencies\League\Container\Exception;

use Imagify\Dependencies\Psr\Container\NotFoundExceptionInterface;
use InvalidArgumentException;

class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
}
