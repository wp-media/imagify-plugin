<?php

declare(strict_types=1);

namespace Imagify\Dependencies\League\Container\Argument\Literal;

use Imagify\Dependencies\League\Container\Argument\LiteralArgument;

class BooleanArgument extends LiteralArgument
{
    public function __construct(bool $value)
    {
        parent::__construct($value, LiteralArgument::TYPE_BOOL);
    }
}
