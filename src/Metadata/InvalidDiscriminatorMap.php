<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

use function sprintf;

final class InvalidDiscriminatorMap extends RuntimeException
{
    /** @param class-string $rootClass */
    public static function classDoesNotExist(string $rootClass, string $value, string $class): self
    {
        return new self(sprintf(
            'The discriminator map of "%s" maps value "%s" to "%s", but that class does not exist.',
            $rootClass,
            $value,
            $class,
        ));
    }

    /**
     * @param class-string $rootClass
     * @param class-string $class
     */
    public static function classIsNotASubtype(string $rootClass, string $value, string $class): self
    {
        return new self(sprintf(
            'The discriminator map of "%s" maps value "%s" to "%s", but that class is not a subtype of "%s".',
            $rootClass,
            $value,
            $class,
            $rootClass,
        ));
    }

    /** @param class-string $rootClass */
    public static function emptyMap(string $rootClass): self
    {
        return new self(sprintf('The discriminator map of "%s" is empty.', $rootClass));
    }
}
