<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use Patchlevel\ODM\Index;

/** @template T of object */
final readonly class DocumentMetadata
{
    /**
     * @param class-string<T> $className
     * @param list<Index> $indexes
     */
    public function __construct(
        public string $className,
        public string $collection,
        public string $idProperty,
        public array $indexes = []
    ) {
    }
}
