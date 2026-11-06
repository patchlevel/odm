<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use ReflectionProperty;

interface FieldMappingResolver
{
    /** @param class-string $className the document class the property is mapped for */
    public function resolve(string $className, ReflectionProperty $reflectionProperty): FieldMapping|null;
}
