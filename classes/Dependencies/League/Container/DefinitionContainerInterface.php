<?php

declare(strict_types=1);

namespace Imagify\Dependencies\League\Container;

use Imagify\Dependencies\League\Container\Definition\DefinitionInterface;
use Imagify\Dependencies\League\Container\Inflector\InflectorInterface;
use Imagify\Dependencies\League\Container\ServiceProvider\ServiceProviderInterface;
use Imagify\Dependencies\Psr\Container\ContainerInterface;

interface DefinitionContainerInterface extends ContainerInterface
{
    public function add(string $id, $concrete = null): DefinitionInterface;
    public function addServiceProvider(ServiceProviderInterface $provider): self;
    public function addShared(string $id, $concrete = null): DefinitionInterface;
    public function extend(string $id): DefinitionInterface;
    public function getNew($id);
    public function inflector(string $type, ?callable $callback = null): InflectorInterface;
}
