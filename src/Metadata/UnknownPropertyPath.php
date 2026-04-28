<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use RuntimeException;

use function implode;
use function sprintf;

final class UnknownPropertyPath extends RuntimeException
{
    /**
     * @param class-string $className
     * @param list<string> $knownProperties
     */
    public function __construct(
        string $className,
        string $propertyPath,
        string $unknownSegment,
        string $mappedPrefix,
        array $knownProperties,
    ) {
        $context = $mappedPrefix !== '' ? $mappedPrefix : '<root>';
        $known = $knownProperties !== [] ? implode(', ', $knownProperties) : '<none>';

        parent::__construct(sprintf(
            'Unknown property path "%s" for class %s: segment "%s" is not mapped under "%s". Known properties: %s.',
            $propertyPath,
            $className,
            $unknownSegment,
            $context,
            $known,
        ));
    }
}
