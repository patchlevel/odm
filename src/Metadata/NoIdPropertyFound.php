<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

use function sprintf;

class NoIdPropertyFound extends RuntimeException
{
    public function __construct(string $className)
    {
        parent::__construct(sprintf('No id property found in class %s.', $className));
    }
}
