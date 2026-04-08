<?php

namespace Patchlevel\ODM\Metadata;

use ReflectionProperty;

interface FieldMappingResolver
{
    public function resolve(ReflectionProperty $reflectionProperty): FieldMapping|null;
}