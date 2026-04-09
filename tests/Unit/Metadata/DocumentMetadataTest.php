<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Metadata;

use Patchlevel\ODM\Metadata\DocumentMetadata;
use Patchlevel\ODM\Metadata\FieldMapping;
use PHPUnit\Framework\TestCase;

final class DocumentMetadataTest extends TestCase
{
    public function testPropertyPathToFieldPathWithoutMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: \stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
        );

        self::assertSame('name', $metadata->propertyPathToFieldPath('name'));
    }

    public function testPropertyPathToFieldPathWithMapping(): void
    {
        $metadata = new DocumentMetadata(
            className: \stdClass::class,
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
            className: \stdClass::class,
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
            className: \stdClass::class,
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
            className: \stdClass::class,
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
            className: \stdClass::class,
            database: null,
            collection: 'test',
            idProperty: 'id',
            fields: [],
        );

        self::assertSame('unknown', $metadata->propertyPathToFieldPath('unknown'));
    }
}
