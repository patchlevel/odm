<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

interface DocumentMetadataFactory
{
    /**
     * @param class-string<T> $className
     *
     * @return DocumentMetadata<T>
     *
     * @template T of object
     */
    public function metadata(string $className): DocumentMetadata;
}
