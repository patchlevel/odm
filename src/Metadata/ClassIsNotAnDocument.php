<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

use function sprintf;

final class ClassIsNotAnDocument extends RuntimeException
{
    /** @param class-string $className */
    public function __construct(string $className)
    {
        parent::__construct(sprintf('Class %s is not an document.', $className));
    }
}
