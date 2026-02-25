<?php

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

class NoIdPropertyFound extends RuntimeException
{

    /**
     * @param string $className
     */
    public function __construct(string $className)
    {
    }
}