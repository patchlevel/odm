<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Hydrator;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\Hydrator\StackHydrator;
use Patchlevel\ODM\Hydrator\DocumentHydrator;
use Patchlevel\ODM\Hydrator\DocumentNotExtractable;
use Patchlevel\ODM\Hydrator\UnknownDiscriminatorValue;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Metadata\DocumentMetadata;
use Patchlevel\ODM\Metadata\StackHydratorFieldMappingResolver;
use Patchlevel\ODM\Tests\Unit\Fixtures\Image;
use Patchlevel\ODM\Tests\Unit\Fixtures\Media;
use Patchlevel\ODM\Tests\Unit\Fixtures\Video;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocumentHydrator::class)]
#[CoversClass(UnknownDiscriminatorValue::class)]
#[CoversClass(DocumentNotExtractable::class)]
final class DocumentHydratorTest extends TestCase
{
    private StackHydrator $stackHydrator;

    private DocumentHydrator $hydrator;

    protected function setUp(): void
    {
        $this->stackHydrator = new StackHydrator();
        $this->hydrator = new DocumentHydrator($this->stackHydrator, $this->metadata());
    }

    public function testExtractWritesTheDiscriminatorValue(): void
    {
        $data = $this->hydrator->extract(new Image('m-1', 'Picture', 800));

        self::assertSame('image', $data['_type']);
        self::assertSame('m-1', $data['_id']);
        self::assertSame(800, $data['width']);
    }

    public function testHydrateResolvesTheConcreteClassFromTheDiscriminator(): void
    {
        $image = $this->hydrator->hydrate(Media::class, $this->hydrator->extract(new Image('m-1', 'Picture', 800)));
        $video = $this->hydrator->hydrate(Media::class, $this->hydrator->extract(new Video('m-2', 'Clip', 60)));

        self::assertInstanceOf(Image::class, $image);
        self::assertSame(800, $image->width);
        self::assertInstanceOf(Video::class, $video);
        self::assertSame(60, $video->duration);
    }

    public function testHydrateWithoutADiscriminatorValueFails(): void
    {
        $this->expectException(UnknownDiscriminatorValue::class);

        $this->hydrator->hydrate(Media::class, ['_id' => 'm-1', 'title' => 'Picture', 'width' => 800]);
    }

    public function testHydrateWithAnUnmappedDiscriminatorValueFails(): void
    {
        $this->expectException(UnknownDiscriminatorValue::class);

        $this->hydrator->hydrate(Media::class, ['_id' => 'm-1', 'title' => 'Picture', '_type' => 'audio']);
    }

    public function testExtractFailsIfTheHydratorDoesNotReturnAnArray(): void
    {
        $hydrator = new DocumentHydrator(
            new class implements Hydrator {
                /** @param array<string, mixed> $context */
                public function hydrate(string $class, mixed $data, array $context = []): object
                {
                    return new $class();
                }

                /** @param array<string, mixed> $context */
                public function extract(object $object, array $context = []): mixed
                {
                    return 'm-1';
                }
            },
            $this->metadata(),
        );

        $this->expectException(DocumentNotExtractable::class);
        $this->expectExceptionMessage('The hydrator returned "string" instead of an array for the document "' . Image::class . '".');

        $hydrator->extract(new Image('m-1', 'Picture', 800));
    }

    /** @return DocumentMetadata<Media> */
    private function metadata(): DocumentMetadata
    {
        return (new AttributeDocumentMetadataFactory(new StackHydratorFieldMappingResolver($this->stackHydrator)))
            ->metadata(Media::class);
    }
}
