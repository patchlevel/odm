<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

use function sprintf;

class MultipleIdPropertiesFound extends RuntimeException
{
    public function __construct(string $className)
    {
        parent::__construct(sprintf('Multiple id properties found in class %s.', $className));
    }
}
