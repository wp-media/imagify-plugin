<?php

declare(strict_types=1);

namespace Imagify\Dependencies\League\Container\Inflector;

use IteratorAggregate;
use Imagify\Dependencies\League\Container\ContainerAwareInterface;

interface InflectorAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    public function add(string $type, ?callable $callback = null): Inflector;
    public function inflect(object $object);
}
