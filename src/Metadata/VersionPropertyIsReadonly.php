<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

use function sprintf;

class VersionPropertyIsReadonly extends RuntimeException
{
    /** @param class-string $className */
    public function __construct(string $className, string $propertyName)
    {
        parent::__construct(sprintf(
            'Version property "%s" in class %s must not be readonly, because the repository writes the '
            . 'incremented version back onto the document after a successful update.',
            $propertyName,
            $className,
        ));
    }
}
