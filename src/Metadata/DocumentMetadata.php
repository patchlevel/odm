<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use Patchlevel\ODM\Index;
use ReflectionProperty;

use function array_is_list;
use function array_keys;
use function array_map;
use function assert;
use function explode;
use function implode;
use function is_array;
use function is_int;
use function str_starts_with;

/** @template T of object */
final readonly class DocumentMetadata
{
    private ReflectionProperty|null $versionReflection;

    /**
     * @param class-string<T>             $className
     * @param list<Index>                 $indexes
     * @param array<string, FieldMapping> $fields
     */
    public function __construct(
        public string $className,
        public string|null $database,
        public string $collection,
        public string $idProperty,
        public array $indexes = [],
        public array $fields = [],
        public string|null $versionProperty = null,
    ) {
        $this->versionReflection = $versionProperty !== null
            ? new ReflectionProperty($className, $versionProperty)
            : null;
    }

    /**
     * Storage field name of the version property, or null when the document is not versioned.
     */
    public function versionField(): string|null
    {
        if ($this->versionProperty === null) {
            return null;
        }

        return $this->fields[$this->versionProperty]->fieldName;
    }

    /**
     * Read the current version from the document, or null when it is not versioned.
     *
     * @param T $document
     */
    public function readVersion(object $document): int|null
    {
        if ($this->versionReflection === null) {
            return null;
        }

        $version = $this->versionReflection->getValue($document);
        assert(is_int($version));

        return $version;
    }

    /**
     * Write the incremented version back onto the document after a successful update. Does nothing
     * when the document is not versioned.
     *
     * @param T $document
     */
    public function writeVersion(object $document, int $version): void
    {
        $this->versionReflection?->setValue($document, $version);
    }

    public function propertyPathToFieldPath(string $propertyPath): string
    {
        return $this->propertyPathToFieldPathWithChildren($propertyPath, $this->fields)[0];
    }

    /**
     * @param array<string, mixed> $filter
     *
     * @return array<string, mixed>
     */
    public function mapFilterToFieldPaths(array $filter): array
    {
        return $this->mapFilterToFieldPathsWithFields($filter, $this->fields);
    }

    /**
     * @param array<string, mixed>        $filter
     * @param array<string, FieldMapping> $fields
     *
     * @return array<string, mixed>
     */
    private function mapFilterToFieldPathsWithFields(array $filter, array $fields): array
    {
        $result = [];

        foreach ($filter as $key => $value) {
            if (str_starts_with($key, '$')) {
                if (is_array($value)) {
                    if (array_is_list($value)) {
                        $result[$key] = array_map(
                            fn (mixed $item): mixed => is_array($item)
                                ? $this->mapFilterToFieldPathsWithFields($item, $fields)
                                : $item,
                            $value,
                        );
                    } else {
                        $result[$key] = $this->mapFilterToFieldPathsWithFields($value, $fields);
                    }
                } else {
                    $result[$key] = $value;
                }

                continue;
            }

            [$fieldPath, $childFields] = $this->propertyPathToFieldPathWithChildren($key, $fields);

            $result[$fieldPath] = is_array($value) ? $this->mapFilterToFieldPathsWithFields($value, $childFields) : $value;
        }

        return $result;
    }

    /**
     * @param array<string, FieldMapping> $fields
     *
     * @return array{0: string, 1: array<string, FieldMapping>}
     */
    private function propertyPathToFieldPathWithChildren(string $propertyPath, array $fields): array
    {
        $parts = explode('.', $propertyPath);
        $fieldParts = [];
        $mappedPropertyParts = [];

        foreach ($parts as $part) {
            if (!isset($fields[$part])) {
                throw new UnknownPropertyPath(
                    $this->className,
                    $propertyPath,
                    $part,
                    implode('.', $mappedPropertyParts),
                    array_keys($fields),
                );
            }

            $field = $fields[$part];
            $fieldParts[] = $field->fieldName;
            $mappedPropertyParts[] = $part;
            $fields = $field->children;
        }

        return [implode('.', $fieldParts), $fields];
    }

    /**
     * @param array<string, 'asc' | 'desc'> $orderBy
     *
     * @return array<string, -1|1>
     */
    public function mapSortingToFieldPaths(array $orderBy): array
    {
        $result = [];

        foreach ($orderBy as $propertyPath => $direction) {
            $result[$this->propertyPathToFieldPath($propertyPath)] = $direction === 'desc' ? -1 : 1;
        }

        return $result;
    }
}
