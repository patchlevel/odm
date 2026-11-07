<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

interface FieldMappingResolver
{
    /**
     * @param class-string $className
     *
     * @return array<string, FieldMapping> keyed by property name
     */
    public function resolve(string $className): array;
}
