<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Metadata;

use Patchlevel\ODM\Attribute\DiscriminatorMap;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Index;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Metadata\DiscriminatorFieldConflict;
use Patchlevel\ODM\Metadata\DocumentMetadata;
use Patchlevel\ODM\Metadata\FieldMapping;
use Patchlevel\ODM\Metadata\FieldMappingResolver;
use Patchlevel\ODM\Metadata\InvalidDiscriminatorMap;
use Patchlevel\ODM\Tests\Unit\Fixtures\Address;
use Patchlevel\ODM\Tests\Unit\Fixtures\Image;
use Patchlevel\ODM\Tests\Unit\Fixtures\Media;
use Patchlevel\ODM\Tests\Unit\Fixtures\Video;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function array_keys;

#[CoversClass(AttributeDocumentMetadataFactory::class)]
#[CoversClass(DocumentMetadata::class)]
#[CoversClass(InvalidDiscriminatorMap::class)]
#[CoversClass(DiscriminatorFieldConflict::class)]
final class DiscriminatorMetadataFactoryTest extends TestCase
{
    public function testRootMetadataSpansEveryType(): void
    {
        $factory = new AttributeDocumentMetadataFactory();
        $metadata = $factory->metadata(Media::class);

        self::assertSame(Media::class, $metadata->className);
        self::assertSame('media', $metadata->collection);
        self::assertSame('id', $metadata->idProperty);
        self::assertSame('_type', $metadata->discriminatorField);
        self::assertSame(['image' => Image::class, 'video' => Video::class], $metadata->discriminatorMap);
        self::assertSame(['image', 'video'], $metadata->discriminatorValues);
        self::assertSame([], $metadata->discriminatorFilter());
    }

    public function testLeafMetadataInheritsCollectionAndRestrictsType(): void
    {
        $factory = new AttributeDocumentMetadataFactory();
        $metadata = $factory->metadata(Image::class);

        self::assertSame(Image::class, $metadata->className);
        self::assertSame('media', $metadata->collection);
        self::assertSame('id', $metadata->idProperty);
        self::assertSame(['image'], $metadata->discriminatorValues);
        self::assertSame(['_type' => ['$in' => ['image']]], $metadata->discriminatorFilter());
    }

    public function testIndexesComeFromTheRoot(): void
    {
        $factory = new AttributeDocumentMetadataFactory();
        $metadata = $factory->metadata(Video::class);

        self::assertEquals([new Index('by_type', ['id' => 'asc'])], $metadata->indexes);
    }

    public function testFieldsAreUnionedAcrossTheHierarchy(): void
    {
        $factory = new AttributeDocumentMetadataFactory($this->fieldResolver());
        $metadata = $factory->metadata(Image::class);

        self::assertSame(['id', 'title', 'width', 'format', 'duration'], array_keys($metadata->fields));
    }

    public function testConflictingFieldMappingAcrossSubclasses(): void
    {
        $resolver = new class implements FieldMappingResolver {
            public function resolve(ReflectionProperty $reflectionProperty): FieldMapping
            {
                $name = $reflectionProperty->getName();

                if ($name === 'format' && $reflectionProperty->getDeclaringClass()->getShortName() === 'Video') {
                    return new FieldMapping('video_format');
                }

                return new FieldMapping($name);
            }
        };

        $factory = new AttributeDocumentMetadataFactory($resolver);

        $this->expectException(DiscriminatorFieldConflict::class);

        $factory->metadata(Media::class);
    }

    public function testMapEntryThatIsNotASubtype(): void
    {
        $class = new #[Document('things')]
        #[DiscriminatorMap(['weird' => Address::class])]
        class ('1') {
            public function __construct(
                #[Id]
                public string $id,
            ) {
            }
        };

        $factory = new AttributeDocumentMetadataFactory();

        $this->expectException(InvalidDiscriminatorMap::class);

        $factory->metadata($class::class);
    }

    public function testEmptyMap(): void
    {
        $class = new #[Document('things')]
        #[DiscriminatorMap([])]
        class ('1') {
            public function __construct(
                #[Id]
                public string $id,
            ) {
            }
        };

        $factory = new AttributeDocumentMetadataFactory();

        $this->expectException(InvalidDiscriminatorMap::class);

        $factory->metadata($class::class);
    }

    private function fieldResolver(): FieldMappingResolver
    {
        return new class implements FieldMappingResolver {
            public function resolve(ReflectionProperty $reflectionProperty): FieldMapping
            {
                return new FieldMapping($reflectionProperty->getName());
            }
        };
    }
}
