<?php

namespace Patchlevel\ODM\Metadata;

use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
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

        $collection = $attributes[0]->newInstance()->collection;
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
            $collection,
            $idProperty,
            $this->indexes($reflection),
        );
    }

    /**
     * @param ReflectionClass $reflection
     * @return list<Index>
     */
    private function indexes(ReflectionClass $reflection): array
    {
        $attributes = $reflection->getAttributes(Index::class);

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