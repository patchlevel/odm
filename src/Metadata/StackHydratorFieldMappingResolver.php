<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\PropertyMetadata;
use Patchlevel\Hydrator\Normalizer\ArrayNormalizer;
use Patchlevel\Hydrator\Normalizer\ArrayShapeNormalizer;
use Patchlevel\Hydrator\Normalizer\Normalizer;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;
use Patchlevel\Hydrator\StackHydrator;

final readonly class StackHydratorFieldMappingResolver implements FieldMappingResolver
{
    public function __construct(
        private StackHydrator $hydrator,
    ) {
    }

    /**
     * @param class-string $className
     *
     * @return array<string, FieldMapping>
     *
     * @throws DocumentHasClassNormalizer
     */
    public function resolve(string $className): array
    {
        $metadata = $this->hydrator->metadata($className);

        if ($metadata->normalizer !== null) {
            throw new DocumentHasClassNormalizer($className, $metadata->normalizer::class);
        }

        return $this->resolveProperties($metadata);
    }

    /** @return array<string, FieldMapping> */
    private function resolveProperties(ClassMetadata $metadata): array
    {
        $fields = [];

        foreach ($metadata->properties as $property) {
            $fields[$property->propertyName] = $this->resolvePropertyMetadata($property);
        }

        return $fields;
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
        $metadata = $this->hydrator->metadata($objectNormalizer->className());

        return new FieldMapping(
            $fieldName,
            $this->resolveProperties($metadata),
        );
    }

    private function resolveArrayShape(string $fieldName, ArrayShapeNormalizer $arrayShapeNormalizer): FieldMapping
    {
        /** @var array<string, FieldMapping> $children */
        $children = [];

        foreach ($arrayShapeNormalizer->innerNormalizers() as $key => $normalizer) {
            $children[$key] = $this->resolveNormalizer($key, $normalizer);
        }

        return new FieldMapping(
            $fieldName,
            $children,
        );
    }
}
