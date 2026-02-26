<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

class NoIdPropertyFound extends RuntimeException
{
    public function __construct(string $className)
    {
    }
}
