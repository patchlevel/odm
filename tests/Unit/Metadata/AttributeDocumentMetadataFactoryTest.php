<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Metadata;

use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Index;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Metadata\ClassIsNotAnDocument;
use Patchlevel\ODM\Metadata\DocumentMetadata;
use Patchlevel\ODM\Metadata\FieldMapping;
use Patchlevel\ODM\Metadata\FieldMappingResolver;
use Patchlevel\ODM\Metadata\MultipleIdPropertiesFound;
use Patchlevel\ODM\Metadata\NoIdPropertyFound;
use Patchlevel\ODM\Tests\Unit\Fixtures\Address;
use Patchlevel\ODM\Tests\Unit\Fixtures\Profile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(AttributeDocumentMetadataFactory::class)]
#[CoversClass(ClassIsNotAnDocument::class)]
#[CoversClass(NoIdPropertyFound::class)]
#[CoversClass(MultipleIdPropertiesFound::class)]
#[CoversClass(FieldMapping::class)]
final class AttributeDocumentMetadataFactoryTest extends TestCase
{
    public function testMetadata(): void
    {
        $expected = new DocumentMetadata(
            className: Profile::class,
            database: null,
            collection: 'rango_documents',
            idProperty: 'id',
            indexes: [
                new Index('by_status', ['status' => 'asc'], unique: false),
                new Index('by_status_other', ['status' => 'desc'], unique: false),
            ],
            fields: ['id' => new FieldMapping('_id', [])],
        );

        $factory = new AttributeDocumentMetadataFactory();
        $metadata = $factory->metadata(Profile::class);

        self::assertEquals($expected, $metadata);
    }

    public function testMetadataOnNonDocumentExpectClassIsNotAnDocument(): void
    {
        $factory = new AttributeDocumentMetadataFactory();
        $this->expectException(ClassIsNotAnDocument::class);

        $factory->metadata(Address::class);
    }

    public function testMetadataInMemoryCache(): void
    {
        $factory = new AttributeDocumentMetadataFactory();
        $metadataFirst = $factory->metadata(Profile::class);
        $metadataSecond = $factory->metadata(Profile::class);

        self::assertSame($metadataFirst, $metadataSecond);
    }

    public function testMetadataIdNotFirstProperty(): void
    {
        $class = new #[Document('rango_documents')]
        class () {
            public function __construct(
                public string $notId = '1',
                #[Id]
                public string $id = 'a',
            ) {
            }
        };

        $factory = new AttributeDocumentMetadataFactory();
        $metadata = $factory->metadata($class::class);

        self::assertSame('id', $metadata->idProperty);
    }

    public function testMetadataNonIdDocument(): void
    {
        $class = new #[Document('rango_documents')]
        class () {
            public function __construct(
                public string $id = '1',
            ) {
            }
        };

        $factory = new AttributeDocumentMetadataFactory();
        $this->expectException(NoIdPropertyFound::class);

        $factory->metadata($class::class);
    }

    public function testMetadataNonIdDocumentWithoutProperties(): void
    {
        $class = new #[Document('rango_documents')]
        class () {
        };

        $factory = new AttributeDocumentMetadataFactory();
        $this->expectException(NoIdPropertyFound::class);

        $factory->metadata($class::class);
    }

    public function testMetadataMultipleIdDocument(): void
    {
        $class = new #[Document('rango_documents')]
        class ('1', '2') {
            public function __construct(
                #[Id]
                public string $id,
                #[Id]
                public string $id2,
            ) {
            }
        };

        $factory = new AttributeDocumentMetadataFactory();
        $this->expectException(MultipleIdPropertiesFound::class);

        $factory->metadata($class::class);
    }

    public function testMetadataWithFieldResolver(): void
    {
        $expected = new DocumentMetadata(
            className: Profile::class,
            database: null,
            collection: 'rango_documents',
            idProperty: 'id',
            indexes: [
                new Index('by_status', ['status' => 'asc'], unique: false),
                new Index('by_status_other', ['status' => 'desc'], unique: false),
            ],
            fields: [
                'id' => new FieldMapping('_id', []),
                'status' => new FieldMapping('_newStatus', []),
            ],
        );

        $fieldResolver = new class implements FieldMappingResolver
        {
            public function resolve(ReflectionProperty $reflectionProperty): FieldMapping|null
            {
                if ($reflectionProperty->getName() === 'status') {
                    return new FieldMapping('_newStatus', []);
                }

                return null;
            }
        };

        $factory = new AttributeDocumentMetadataFactory($fieldResolver);
        $metadata = $factory->metadata(Profile::class);

        self::assertEquals($expected, $metadata);
    }

    public function testMetadataWithFieldResolverNotUsedForId(): void
    {
        $expected = new DocumentMetadata(
            className: Profile::class,
            database: null,
            collection: 'rango_documents',
            idProperty: 'id',
            indexes: [
                new Index('by_status', ['status' => 'asc'], unique: false),
                new Index('by_status_other', ['status' => 'desc'], unique: false),
            ],
            fields: ['id' => new FieldMapping('_id', [], '_otherId')],
        );

        $fieldResolver = new class implements FieldMappingResolver
        {
            public function resolve(ReflectionProperty $reflectionProperty): FieldMapping|null
            {
                if ($reflectionProperty->getName() === 'id') {
                    return new FieldMapping('_otherId', []);
                }

                return null;
            }
        };

        $factory = new AttributeDocumentMetadataFactory($fieldResolver);
        $metadata = $factory->metadata(Profile::class);

        self::assertEquals($expected, $metadata);
    }
}
