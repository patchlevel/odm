<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

class MultipleIdPropertiesFound extends RuntimeException
{
    public function __construct(string $className)
    {
    }
}
