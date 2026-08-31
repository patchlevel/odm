<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Hydrator;

use Patchlevel\Hydrator\ClassNotSupported;
use Patchlevel\Hydrator\HydratorWithContext;
use Patchlevel\ODM\Metadata\DocumentMetadata;

use function array_keys;
use function array_values;
use function is_string;

final class DocumentHydrator implements HydratorWithContext
{
    private const ID_FIELD_NAME = '_id';

    private string|null $fieldNameOverride;

    public function __construct(
        private readonly HydratorWithContext $hydrator,
        private readonly DocumentMetadata $documentMetadata,
    ) {
        $this->fieldNameOverride = $this->documentMetadata->fields[$this->documentMetadata->idProperty]->fieldNameOverride;
    }

    /**
     * @param class-string<T>      $class
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return T
     *
     * @throws ClassNotSupported if the class is not supported or not found.
     *
     * @template T of object
     */
    public function hydrate(string $class, array $data, array $context = []): object
    {
        if ($this->fieldNameOverride) {
            $data[$this->fieldNameOverride] = $data[self::ID_FIELD_NAME];
            unset($data[self::ID_FIELD_NAME]);
        }

        $discriminatorField = $this->documentMetadata->discriminatorField;

        if ($discriminatorField !== null) {
            $value = $data[$discriminatorField] ?? null;

            if (!is_string($value)) {
                throw UnknownDiscriminatorValue::missing($this->documentMetadata->className, $discriminatorField);
            }

            $concreteClass = $this->documentMetadata->classForDiscriminator($value);

            if ($concreteClass === null) {
                throw UnknownDiscriminatorValue::notMapped(
                    $this->documentMetadata->className,
                    $discriminatorField,
                    $value,
                    array_keys($this->documentMetadata->discriminatorMap),
                );
            }

            /** @var class-string<T> $class */
            $class = $concreteClass;
        }

        return $this->hydrator->hydrate($class, $data, $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function extract(object $object, array $context = []): array
    {
        $data = $this->hydrator->extract($object, $context);

        if ($this->fieldNameOverride) {
            $data[self::ID_FIELD_NAME] = $data[$this->fieldNameOverride];
            unset($data[$this->fieldNameOverride]);
        }

        $discriminatorField = $this->documentMetadata->discriminatorField;

        if ($discriminatorField !== null) {
            $value = $this->documentMetadata->discriminatorForClass($object::class);

            if ($value === null) {
                throw new ClassNotInDiscriminatorMap(
                    $object::class,
                    array_values($this->documentMetadata->discriminatorMap),
                );
            }

            $data[$discriminatorField] = $value;
        }

        return $data;
    }
}
