<?php

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

final class ClassIsNotAnDocument extends RuntimeException
{
    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        parent::__construct(sprintf('Class %s is not an document.', $className));
    }
}