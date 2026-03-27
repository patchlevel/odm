<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index as IndexAttribute;
use Patchlevel\ODM\Index;
use ReflectionClass;

final readonly class AttributeDocumentMetadataFactory implements DocumentMetadataFactory
{
    /**
     * @param class-string<T> $className
     *
     * @return DocumentMetadata<T>
     *
     * @template T of object
     */
    public function metadata(string $className): DocumentMetadata
    {
        $reflection = new ReflectionClass($className);

        $attributes = $reflection->getAttributes(Document::class);

        if ($attributes === []) {
            throw new ClassIsNotAnDocument($className);
        }

        $attribute = $attributes[0]->newInstance();

        $collection = $attribute->collection;
        $database = $attribute->database;
        $idProperty = null;

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $attributes = $reflectionProperty->getAttributes(Id::class);

            if ($attributes === []) {
                continue;
            }

            if ($idProperty !== null) {
                throw new MultipleIdPropertiesFound($className);
            }

            $idProperty = $reflectionProperty->getName();
        }

        if ($idProperty === null) {
            throw new NoIdPropertyFound($className);
        }

        return new DocumentMetadata(
            $className,
            $database,
            $collection,
            $idProperty,
            $this->indexes($reflection),
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
}
