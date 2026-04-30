<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index as IndexAttribute;
use Patchlevel\ODM\Index;
use ReflectionClass;

final class AttributeDocumentMetadataFactory implements DocumentMetadataFactory
{
    /** @var array<class-string<object>, DocumentMetadata<object>> */
    private array $metadataCache = [];

    public function __construct(
        private readonly FieldMappingResolver|null $fieldResolver = null,
    ) {
    }

    /**
     * @param class-string<T> $className
     *
     * @return DocumentMetadata<T>
     *
     * @template T of object
     */
    public function metadata(string $className): DocumentMetadata
    {
        if (isset($this->metadataCache[$className])) {
            return $this->metadataCache[$className];
        }

        $reflection = new ReflectionClass($className);

        $attributes = $reflection->getAttributes(Document::class);

        if ($attributes === []) {
            throw new ClassIsNotAnDocument($className);
        }

        $attribute = $attributes[0]->newInstance();

        $collection = $attribute->collection;
        $database = $attribute->database;
        $fields = [];
        $idProperty = $this->getIdProperty($reflection);

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $field = $this->fieldResolver?->resolve($reflectionProperty);

            if ($idProperty === $reflectionProperty->getName()) {
                $fields[$reflectionProperty->getName()] = new FieldMapping('_id', [], $field?->fieldName);

                continue;
            }

            if (!$field) {
                continue;
            }

            $fields[$reflectionProperty->getName()] = $field;
        }

        return $this->metadataCache[$className] = new DocumentMetadata(
            $className,
            $database,
            $collection,
            $idProperty,
            $this->indexes($reflection),
            $fields,
        );
    }

    /**
     * @param ReflectionClass<object> $reflection
     *
     * @return list<Index>
     */
    private function indexes(ReflectionClass $reflection): array
    {
        $attributes = $reflection->getAttributes(IndexAttribute::class);

        $indexes = [];

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();

            $indexes[] = new Index(
                $instance->name,
                $instance->keys,
                $instance->unique,
            );
        }

        return $indexes;
    }

    /** @param ReflectionClass<object> $reflection */
    private function getIdProperty(ReflectionClass $reflection): string
    {
        $idProperty = null;

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $attributes = $reflectionProperty->getAttributes(Id::class);

            if ($attributes === []) {
                continue;
            }

            if ($idProperty !== null) {
                throw new MultipleIdPropertiesFound($reflection->name);
            }

            $idProperty = $reflectionProperty->getName();
        }

        if ($idProperty === null) {
            throw new NoIdPropertyFound($reflection->name);
        }

        return $idProperty;
    }
}
