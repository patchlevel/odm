<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Hydrator;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\MetadataEnricher;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Metadata\ClassIsNotAnDocument;
use Patchlevel\ODM\Metadata\DocumentMetadata;
use Patchlevel\ODM\Metadata\DocumentMetadataFactory;
use RuntimeException;

class ODMMappingMetadataEnricher implements MetadataEnricher
{
    public function __construct(
        private readonly DocumentMetadataFactory $factory = new AttributeDocumentMetadataFactory(),
    ) {
    }

    public function enrich(ClassMetadata $classMetadata): void
    {
        try {
            $documentMetadata = $this->factory->metadata($classMetadata->className);

            $classMetadata->extras[DocumentMetadata::class] = $documentMetadata;

            $propertyMetadata = $classMetadata->properties[$documentMetadata->idProperty] ?? null;

            if ($propertyMetadata === null) {
                throw new RuntimeException();
            }

            if ($propertyMetadata->fieldName !== $propertyMetadata->propertyName) {
                throw new RuntimeException();
            }

            $propertyMetadata->fieldName = '_id';
        } catch (ClassIsNotAnDocument) {
            return;
        }
    }
}
