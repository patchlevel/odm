<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Metadata;

use Patchlevel\ODM\Metadata\DocumentMetadata;
use Patchlevel\ODM\Metadata\FieldMapping;
use Patchlevel\ODM\Metadata\UnknownPropertyPath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(DocumentMetadata::class)]
final class DocumentMetadataTest extends TestCase
{
    public function testDiscriminatorLookupsWithoutInheritance(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        self::assertNull($metadata->classForDiscriminator('image'));
        self::assertNull($metadata->discriminatorForClass(stdClass::class));
        self::assertSame([], $metadata->discriminatorFilter());
    }

    public function testDiscriminatorLookupsResolveBothDirections(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            discriminatorField: '_type',
            discriminatorMap: ['thing' => stdClass::class],
            discriminatorValues: ['thing'],
        );

        self::assertSame(stdClass::class, $metadata->classForDiscriminator('thing'));
        self::assertSame('thing', $metadata->discriminatorForClass(stdClass::class));
        self::assertNull($metadata->classForDiscriminator('other'));
    }

    public function testDiscriminatorFilterRestrictsAPartialHierarchy(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            discriminatorField: '_type',
            discriminatorMap: ['a' => stdClass::class, 'b' => stdClass::class],
            discriminatorValues: ['a'],
        );

        self::assertSame(['_type' => ['$in' => ['a']]], $metadata->discriminatorFilter());
    }

    public function testPropertyPathToFieldPathWithoutMappingThrowsException(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        $this->expectException(UnknownPropertyPath::class);
        $this->expectExceptionMessage('segment "name" is not mapped under "<root>"');

        $metadata->propertyPathToFieldPath('name');
    }

    public function testPropertyPathToFieldPathWithoutMappingNestedThrowsException(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        $this->expectException(UnknownPropertyPath::class);
        $this->expectExceptionMessage('segment "a" is not mapped under "<root>"');

        $metadata->propertyPathToFieldPath('a.b.c');
    }

    public function testPropertyPathToFieldPathWithMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'name' => new FieldMapping('_name'),
            ],
        );

        self::assertSame('_name', $metadata->propertyPathToFieldPath('name'));
    }

    public function testPropertyPathToFieldPathWithNestedMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'address' => new FieldMapping('_address', [
                    'street' => new FieldMapping('_street'),
                ]),
            ],
        );

        self::assertSame('_address._street', $metadata->propertyPathToFieldPath('address.street'));
    }

    public function testPropertyPathToFieldPathWithPartialNestedMappingThrowsException(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'address' => new FieldMapping('_address'),
            ],
        );

        $this->expectException(UnknownPropertyPath::class);
        $this->expectExceptionMessage('segment "street" is not mapped under "address"');

        $metadata->propertyPathToFieldPath('address.street');
    }

    public function testPropertyPathToFieldPathWithDeeplyNestedMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'a' => new FieldMapping('_a', [
                    'b' => new FieldMapping('_b', [
                        'c' => new FieldMapping('_c'),
                    ]),
                ]),
            ],
        );

        self::assertSame('_a._b._c', $metadata->propertyPathToFieldPath('a.b.c'));
    }

    public function testPropertyPathToFieldPathUnmappedFieldThrowsException(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [],
        );

        $this->expectException(UnknownPropertyPath::class);
        $this->expectExceptionMessage('segment "unknown" is not mapped under "<root>"');

        $metadata->propertyPathToFieldPath('unknown');
    }

    public function testMapFilterWithoutMappingThrowsException(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        $this->expectException(UnknownPropertyPath::class);
        $this->expectExceptionMessage('segment "name" is not mapped under "<root>"');

        $metadata->mapFilterToFieldPaths(['name' => 'foo']);
    }

    public function testMapFilterWithMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'name' => new FieldMapping('_name'),
            ],
        );

        self::assertSame(['_name' => 'foo'], $metadata->mapFilterToFieldPaths(['name' => 'foo']));
    }

    public function testMapFilterWithNestedMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'address' => new FieldMapping('_address', [
                    'street' => new FieldMapping('_street'),
                ]),
            ],
        );

        self::assertSame(
            ['_address._street' => 'Main St'],
            $metadata->mapFilterToFieldPaths(['address.street' => 'Main St']),
        );
    }

    public function testMapFilterWithComparisonOperator(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'age' => new FieldMapping('_age'),
            ],
        );

        self::assertSame(
            ['_age' => ['$gt' => 18, '$lt' => 30]],
            $metadata->mapFilterToFieldPaths(['age' => ['$gt' => 18, '$lt' => 30]]),
        );
    }

    public function testMapFilterWithElemMatch(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'tags' => new FieldMapping('_tags', [
                    'value' => new FieldMapping('_value'),
                ]),
            ],
        );

        self::assertSame(
            ['_tags' => ['$elemMatch' => ['_value' => 'php']]],
            $metadata->mapFilterToFieldPaths(['tags' => ['$elemMatch' => ['value' => 'php']]]),
        );
    }

    public function testMapFilterWithAndOperator(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'status' => new FieldMapping('_status'),
                'age' => new FieldMapping('_age'),
            ],
        );

        self::assertSame(
            ['$and' => [['_status' => 'active'], ['_age' => ['$gt' => 18]]]],
            $metadata->mapFilterToFieldPaths(['$and' => [['status' => 'active'], ['age' => ['$gt' => 18]]]]),
        );
    }

    public function testMapFilterWithOrOperator(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'status' => new FieldMapping('_status'),
            ],
        );

        self::assertSame(
            ['$or' => [['_status' => 'active'], ['_status' => 'pending']]],
            $metadata->mapFilterToFieldPaths(['$or' => [['status' => 'active'], ['status' => 'pending']]]),
        );
    }

    public function testMapFilterWithNorOperator(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'status' => new FieldMapping('_status'),
            ],
        );

        self::assertSame(
            ['$nor' => [['_status' => 'deleted'], ['_status' => 'banned']]],
            $metadata->mapFilterToFieldPaths(['$nor' => [['status' => 'deleted'], ['status' => 'banned']]]),
        );
    }

    public function testMapFilterWithNotOperator(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'status' => new FieldMapping('_status'),
            ],
        );

        self::assertSame(
            ['_status' => ['$not' => ['$eq' => 'deleted']]],
            $metadata->mapFilterToFieldPaths(['status' => ['$not' => ['$eq' => 'deleted']]]),
        );
    }

    public function testMapFilterEmptyFilter(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        self::assertSame([], $metadata->mapFilterToFieldPaths([]));
    }

    public function testMapFilterWithValueSameAsPropertyName(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'address' => new FieldMapping('_address', [
                    'street' => new FieldMapping('_street'),
                ]),
            ],
        );

        self::assertSame(
            ['_address._street' => 'address'],
            $metadata->mapFilterToFieldPaths(['address.street' => 'address']),
        );
    }

    public function testMapFilterWithValueSameAsPropertyNameAndOperator(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'address' => new FieldMapping('_address', [
                    'street' => new FieldMapping('_street'),
                ]),
            ],
        );

        self::assertSame(
            ['_address._street' => ['$in' => ['street', 'address']]],
            $metadata->mapFilterToFieldPaths(['address.street' => ['$in' => ['street', 'address']]]),
        );
    }

    public function testMapSortingWithoutMappingThrowsException(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        $this->expectException(UnknownPropertyPath::class);
        $this->expectExceptionMessage('segment "name" is not mapped under "<root>"');

        $metadata->mapSortingToFieldPaths(['name' => 'asc']);
    }

    public function testMapSortingAscWithMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'name' => new FieldMapping('_name'),
            ],
        );

        self::assertSame(['_name' => 1], $metadata->mapSortingToFieldPaths(['name' => 'asc']));
    }

    public function testMapSortingDescWithMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'name' => new FieldMapping('_name'),
            ],
        );

        self::assertSame(['_name' => -1], $metadata->mapSortingToFieldPaths(['name' => 'desc']));
    }

    public function testMapSortingMultipleFields(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'lastName' => new FieldMapping('_lastName'),
                'firstName' => new FieldMapping('_firstName'),
            ],
        );

        self::assertSame(
            ['_lastName' => 1, '_firstName' => -1],
            $metadata->mapSortingToFieldPaths(['lastName' => 'asc', 'firstName' => 'desc']),
        );
    }

    public function testMapSortingWithNestedMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [
                'address' => new FieldMapping('_address', [
                    'city' => new FieldMapping('_city'),
                ]),
            ],
        );

        self::assertSame(
            ['_address._city' => 1],
            $metadata->mapSortingToFieldPaths(['address.city' => 'asc']),
        );
    }

    public function testMapSortingEmptyOrderBy(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        self::assertSame([], $metadata->mapSortingToFieldPaths([]));
    }

    public function testReadAndWriteVersionWithoutVersionProperty(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        $document = new stdClass();

        self::assertNull($metadata->readVersion($document));

        $metadata->writeVersion($document, 5);

        self::assertObjectNotHasProperty('version', $document);
    }

    public function testReadAndWriteVersion(): void
    {
        $document = new class {
            public int $version = 3;
        };

        $metadata = new DocumentMetadata(
            className: $document::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            versionProperty: 'version',
            fields: ['version' => new FieldMapping('version')],
        );

        self::assertSame(3, $metadata->readVersion($document));

        $metadata->writeVersion($document, 4);

        self::assertSame(4, $document->version);
        self::assertSame(4, $metadata->readVersion($document));
    }
}
