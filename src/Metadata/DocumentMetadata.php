<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use Patchlevel\ODM\Index;

/**
 * @template T of object
 */
final readonly class DocumentMetadata
{
    /**
     * @param class-string<T> $className
     * @param list<Index> $indexes
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
        $parts = explode('.', $propertyPath);
        $fields = $this->fields;
        $fieldParts = [];

        foreach ($parts as $part) {
            if (!isset($fields[$part])) {
                $fieldParts[] = $part;
                continue;
            }

            $field = $fields[$part];
            $fieldParts[] = $field->fieldName;
            $fields = $field->children;
        }

        return implode('.', $fieldParts);
    }

    /**
     * @param array<string|int, mixed> $filter
     *
     * @return array<string|int, mixed>
     */
    public function mapFilterToFieldPaths(array $filter): array
    {
        $result = [];

        foreach ($filter as $key => $value) {
            if (!is_string($key)) {
                $result[$key] = is_array($value) ? $this->mapFilterToFieldPaths($value) : $value;
                continue;
            }

            if (str_starts_with($key, '$')) {
                if (is_array($value)) {
                    if (array_is_list($value)) {
                        $result[$key] = array_map(
                            static fn (mixed $item): mixed => is_array($item) ? $this->mapFilterToFieldPaths($item) : $item,
                            $value
                        );
                    } else {
                        $result[$key] = $this->mapFilterToFieldPaths($value);
                    }
                } else {
                    $result[$key] = $value;
                }

                continue;
            }

            $fieldPath = $this->propertyPathToFieldPath($key);

            $result[$fieldPath] = is_array($value) ? $this->mapFilterToFieldPaths($value) : $value;
        }

        return $result;
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
