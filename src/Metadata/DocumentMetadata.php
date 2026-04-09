<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use Patchlevel\ODM\Index;

use function array_is_list;
use function array_map;
use function explode;
use function implode;
use function is_array;
use function str_starts_with;

/** @template T of object */
final readonly class DocumentMetadata
{
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
    ) {
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

        foreach ($parts as $part) {
            if (!isset($fields[$part])) {
                $fieldParts[] = $part;
                $fields = [];
                continue;
            }

            $field = $fields[$part];
            $fieldParts[] = $field->fieldName;
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
