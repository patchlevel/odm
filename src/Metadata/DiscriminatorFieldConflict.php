<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

use function sprintf;

final class DiscriminatorFieldConflict extends RuntimeException
{
    /** @param class-string $rootClass */
    public function __construct(string $rootClass, string $propertyName, string $firstField, string $secondField)
    {
        parent::__construct(sprintf(
            'The subclasses of "%s" map property "%s" to different stored fields ("%s" and "%s"). '
            . 'Properties shared across an inheritance hierarchy must use the same field name.',
            $rootClass,
            $propertyName,
            $firstField,
            $secondField,
        ));
    }
}
