<?php

namespace Patchlevel\ODM\Metadata;

use Patchlevel\Hydrator\Metadata\PropertyMetadata;
use Patchlevel\Hydrator\Normalizer\ArrayNormalizer;
use Patchlevel\Hydrator\Normalizer\ArrayShapeNormalizer;
use Patchlevel\Hydrator\Normalizer\Normalizer;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;
use Patchlevel\Hydrator\StackHydrator;
use ReflectionProperty;

final readonly class StackHydratorFieldMappingResolver implements FieldMappingResolver
{
    public function __construct(
        private StackHydrator $hydrator
    ) {
    }

    public function resolve(ReflectionProperty $reflectionProperty): FieldMapping|null
    {
        $metadata = $this->hydrator->metadata($reflectionProperty->getDeclaringClass()->getName());
        $property = $metadata->properties[$reflectionProperty->getName()];

        return $this->resolvePropertyMetadata($property);
    }

    private function resolvePropertyMetadata(PropertyMetadata $property): FieldMapping
    {
        if (!$property->normalizer) {
            return new FieldMapping($property->fieldName);
        }

        return $this->resolveNormalizer($property->fieldName, $property->normalizer);
    }

    private function resolveNormalizer(string $fieldName, Normalizer $normalizer): FieldMapping
    {
        if ($normalizer instanceof ArrayNormalizer) {
            return $this->resolveNormalizer($fieldName, $normalizer->innerNormalizer());
        }

        if ($normalizer instanceof ObjectNormalizer) {
            return $this->resolveObjectNormalizer($fieldName, $normalizer);
        }

        if ($normalizer instanceof ArrayShapeNormalizer) {
            return $this->resolveArrayShape($fieldName, $normalizer);
        }

        return new FieldMapping($fieldName);
    }

    private function resolveObjectNormalizer(string $fieldName, ObjectNormalizer $objectNormalizer): FieldMapping
    {
        $metadata = $this->hydrator->metadata($objectNormalizer->getClassName());
        $children = [];

        foreach ($metadata->properties as $property) {
            $children[$property->getName()] = $this->resolvePropertyMetadata($property);
        }

        return new FieldMapping(
            $fieldName,
            $children
        );
    }

    private function resolveArrayShape(string $fieldName, ArrayShapeNormalizer $arrayShapeNormalizer): FieldMapping
    {
        $children = [];

        foreach ($arrayShapeNormalizer->innerNormalizers() as $key => $normalizer) {
            $children[$key] = $this->resolveNormalizer($key, $normalizer);
        }

        return new FieldMapping(
            $fieldName,
            $children
        );
    }
}