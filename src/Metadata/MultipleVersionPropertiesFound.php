<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

use function sprintf;

class MultipleVersionPropertiesFound extends RuntimeException
{
    /** @param class-string $className */
    public function __construct(string $className)
    {
        parent::__construct(sprintf('Multiple version properties found in class %s.', $className));
    }
}
