<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Metadata;

use Patchlevel\ODM\Attribute\DiscriminatorMap;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index as IndexAttribute;
use Patchlevel\ODM\Attribute\Version;
use Patchlevel\ODM\Index;
use ReflectionClass;

use function array_keys;
use function array_unique;
use function array_values;
use function class_exists;
use function is_a;

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
        $rootReflection = $this->documentReflection($reflection);

        $attribute = $rootReflection->getAttributes(Document::class)[0]->newInstance();

        $idProperty = $this->getIdProperty($reflection);
        $versionProperty = $this->getVersionProperty($reflection);

        [$discriminatorField, $discriminatorMap] = $this->discriminator($rootReflection);

        $fieldClasses = $discriminatorMap === []
            ? [$className]
            : array_values(array_unique([$rootReflection->getName(), ...array_values($discriminatorMap)]));

        $discriminatorValues = [];

        foreach ($discriminatorMap as $value => $mappedClass) {
            if (!is_a($mappedClass, $className, true)) {
                continue;
            }

            $discriminatorValues[] = $value;
        }

        return $this->metadataCache[$className] = new DocumentMetadata(
            $className,
            $attribute->database,
            $attribute->collection,
            $idProperty,
            $this->indexes($rootReflection),
            $this->resolveFields($rootReflection->getName(), $fieldClasses, $idProperty, $versionProperty),
            $versionProperty,
            $discriminatorField,
            $discriminatorMap,
            $discriminatorValues,
        );
    }

    /**
     * @param ReflectionClass<object> $reflection
     *
     * @return ReflectionClass<object>
     */
    private function documentReflection(ReflectionClass $reflection): ReflectionClass
    {
        $current = $reflection;

        while ($current !== false) {
            if ($current->getAttributes(Document::class) !== []) {
                return $current;
            }

            $current = $current->getParentClass();
        }

        throw new ClassIsNotAnDocument($reflection->getName());
    }

    /**
     * @param ReflectionClass<object> $rootReflection
     *
     * @return array{0: string|null, 1: array<string, class-string>}
     */
    private function discriminator(ReflectionClass $rootReflection): array
    {
        $attributes = $rootReflection->getAttributes(DiscriminatorMap::class);

        if ($attributes === []) {
            return [null, []];
        }

        $discriminator = $attributes[0]->newInstance();
        $rootClass = $rootReflection->getName();

        if ($discriminator->map === []) {
            throw InvalidDiscriminatorMap::emptyMap($rootClass);
        }

        foreach ($discriminator->map as $value => $class) {
            if (!class_exists($class)) {
                throw InvalidDiscriminatorMap::classDoesNotExist($rootClass, $value, $class);
            }

            if (!is_a($class, $rootClass, true)) {
                throw InvalidDiscriminatorMap::classIsNotASubtype($rootClass, $value, $class);
            }
        }

        return [$discriminator->field, $discriminator->map];
    }

    /**
     * @param class-string       $rootClass
     * @param list<class-string> $classNames
     *
     * @return array<string, FieldMapping>
     */
    private function resolveFields(
        string $rootClass,
        array $classNames,
        string $idProperty,
        string|null $versionProperty,
    ): array {
        $fields = [];

        foreach ($classNames as $className) {
            $reflection = new ReflectionClass($className);

            foreach ($reflection->getProperties() as $reflectionProperty) {
                $name = $reflectionProperty->getName();
                $field = $this->fieldResolver?->resolve($reflectionProperty);

                if ($idProperty === $name) {
                    $mapping = new FieldMapping('_id', [], $field?->fieldName);
                } elseif ($versionProperty === $name) {
                    $mapping = $field ?? new FieldMapping($name);
                } elseif ($field !== null) {
                    $mapping = $field;
                } else {
                    continue;
                }

                if (isset($fields[$name])) {
                    if (!$this->fieldMappingEquals($fields[$name], $mapping)) {
                        throw new DiscriminatorFieldConflict(
                            $rootClass,
                            $name,
                            $fields[$name]->fieldName,
                            $mapping->fieldName,
                        );
                    }

                    continue;
                }

                $fields[$name] = $mapping;
            }
        }

        return $fields;
    }

    private function fieldMappingEquals(FieldMapping $a, FieldMapping $b): bool
    {
        if ($a->fieldName !== $b->fieldName || $a->fieldNameOverride !== $b->fieldNameOverride) {
            return false;
        }

        if (array_keys($a->children) !== array_keys($b->children)) {
            return false;
        }

        foreach ($a->children as $key => $child) {
            if (!$this->fieldMappingEquals($child, $b->children[$key])) {
                return false;
            }
        }

        return true;
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

    /** @param ReflectionClass<object> $reflection */
    private function getVersionProperty(ReflectionClass $reflection): string|null
    {
        $versionProperty = null;

        foreach ($reflection->getProperties() as $reflectionProperty) {
            $attributes = $reflectionProperty->getAttributes(Version::class);

            if ($attributes === []) {
                continue;
            }

            if ($versionProperty !== null) {
                throw new MultipleVersionPropertiesFound($reflection->name);
            }

            if ($reflectionProperty->isReadOnly()) {
                throw new VersionPropertyIsReadonly($reflection->name, $reflectionProperty->getName());
            }

            $versionProperty = $reflectionProperty->getName();
        }

        return $versionProperty;
    }
}
