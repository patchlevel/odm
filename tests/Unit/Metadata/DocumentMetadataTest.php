<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Metadata;

use Patchlevel\ODM\Metadata\DocumentMetadata;
use Patchlevel\ODM\Metadata\FieldMapping;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DocumentMetadataTest extends TestCase
{
    public function testPropertyPathToFieldPathWithoutMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        self::assertSame('name', $metadata->propertyPathToFieldPath('name'));
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

    public function testPropertyPathToFieldPathWithPartialNestedMapping(): void
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

        self::assertSame('_address.street', $metadata->propertyPathToFieldPath('address.street'));
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

    public function testPropertyPathToFieldPathUnmappedFieldPassedThrough(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [],
        );

        self::assertSame('unknown', $metadata->propertyPathToFieldPath('unknown'));
    }

    public function testMapFilterWithoutMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        self::assertSame(['name' => 'foo'], $metadata->mapFilterToFieldPaths(['name' => 'foo']));
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
            ['_age' => ['$gt' => 18]],
            $metadata->mapFilterToFieldPaths(['age' => ['$gt' => 18]]),
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

    public function testMapSortingWithoutMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        self::assertSame(['name' => 1], $metadata->mapSortingToFieldPaths(['name' => 'asc']));
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
}
