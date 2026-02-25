<?php

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

class MultipleIdPropertiesFound extends RuntimeException
{

    /**
     * @param string $className
     */
    public function __construct(string $className)
    {
    }
}