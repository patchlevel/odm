<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Hydrator;

use RuntimeException;

use function implode;
use function sprintf;

final class UnknownDiscriminatorValue extends RuntimeException
{
    /** @param class-string $rootClass */
    public static function missing(string $rootClass, string $field): self
    {
        return new self(sprintf(
            'The document for "%s" has no value in its discriminator field "%s".',
            $rootClass,
            $field,
        ));
    }

    /**
     * @param class-string $rootClass
     * @param list<string> $knownValues
     */
    public static function notMapped(string $rootClass, string $field, string $value, array $knownValues): self
    {
        return new self(sprintf(
            'The discriminator field "%s" of "%s" holds value "%s", which is not part of its discriminator map. '
            . 'Known values: %s.',
            $field,
            $rootClass,
            $value,
            $knownValues !== [] ? implode(', ', $knownValues) : '<none>',
        ));
    }
}
