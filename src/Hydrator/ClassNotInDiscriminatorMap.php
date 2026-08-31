<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Hydrator;

use RuntimeException;

use function implode;
use function sprintf;

final class ClassNotInDiscriminatorMap extends RuntimeException
{
    /**
     * @param class-string       $class
     * @param list<class-string> $knownClasses
     */
    public function __construct(string $class, array $knownClasses)
    {
        parent::__construct(sprintf(
            'Class "%s" is not part of the discriminator map. Mapped classes: %s.',
            $class,
            $knownClasses !== [] ? implode(', ', $knownClasses) : '<none>',
        ));
    }
}
