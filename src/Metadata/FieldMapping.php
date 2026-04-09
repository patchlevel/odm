<?php

namespace Patchlevel\ODM\Metadata;

final readonly class FieldMapping
{
    /**
     * @param array<string, FieldMapping> $children
     */
    public function __construct(
        public string $fieldName,
        public array $children = [],
    ) {
    }
}