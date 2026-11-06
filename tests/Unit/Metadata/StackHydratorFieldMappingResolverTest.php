<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Metadata;

use Patchlevel\Hydrator\Guesser\BuiltInGuesser;
use Patchlevel\Hydrator\Metadata\AttributeMetadataFactory;
use Patchlevel\Hydrator\StackHydrator;
use Patchlevel\ODM\Metadata\DocumentHasClassNormalizer;
use Patchlevel\ODM\Metadata\FieldMapping;
use Patchlevel\ODM\Metadata\StackHydratorFieldMappingResolver;
use Patchlevel\ODM\Tests\Unit\Fixtures\DocumentBase;
use Patchlevel\ODM\Tests\Unit\Fixtures\NormalizedContract;
use Patchlevel\ODM\Tests\Unit\Fixtures\PersonalData;
use Patchlevel\ODM\Tests\Unit\Fixtures\Profile;
use Patchlevel\ODM\Tests\Unit\Fixtures\SkillNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function sprintf;

#[CoversClass(StackHydratorFieldMappingResolver::class)]
#[CoversClass(DocumentHasClassNormalizer::class)]
final class StackHydratorFieldMappingResolverTest extends TestCase
{
    private StackHydratorFieldMappingResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new StackHydratorFieldMappingResolver(
            new StackHydrator(
                new AttributeMetadataFactory(
                    null,
                    new BuiltInGuesser(),
                ),
            ),
        );
    }

    public function testResolvePropertyWithoutNormalizer(): void
    {
        $mapping = $this->resolver->resolve(PersonalData::class, new ReflectionProperty(PersonalData::class, 'name'));

        self::assertEquals(new FieldMapping('_name'), $mapping);
    }

    public function testResolvePropertyWithObjectNormalizer(): void
    {
        $mapping = $this->resolver->resolve(Profile::class, new ReflectionProperty(Profile::class, 'personalData'));

        self::assertEquals(
            new FieldMapping('_personal_data', [
                'name' => new FieldMapping('_name'),
                'age' => new FieldMapping('_age'),
            ]),
            $mapping,
        );
    }

    public function testResolvePropertyWithArrayOfObjectNormalizer(): void
    {
        $mapping = $this->resolver->resolve(Profile::class, new ReflectionProperty(Profile::class, 'addresses'));

        self::assertEquals(
            new FieldMapping('_addresses', [
                'street' => new FieldMapping('_street'),
                'city' => new FieldMapping('_city'),
            ]),
            $mapping,
        );
    }

    public function testResolvePropertyWithCustomNormalizer(): void
    {
        $mapping = $this->resolver->resolve(Profile::class, new ReflectionProperty(Profile::class, 'skills'));

        self::assertEquals(
            new FieldMapping('_skills'),
            $mapping,
        );
    }

    public function testResolvePropertyWithArrayShapeNormalizer(): void
    {
        $mapping = $this->resolver->resolve(Profile::class, new ReflectionProperty(Profile::class, 'stats'));

        self::assertEquals(
            new FieldMapping('_stats', [
                'addresses' => new FieldMapping('addresses', [
                    'street' => new FieldMapping('_street'),
                    'city' => new FieldMapping('_city'),
                ]),
            ]),
            $mapping,
        );
    }

    public function testResolveFailsForADocumentWithAClassNormalizer(): void
    {
        $document = new #[SkillNormalizer]
        class ('1') extends DocumentBase {
        };

        $this->expectException(DocumentHasClassNormalizer::class);
        $this->expectExceptionMessage(sprintf(
            'The document "%s" has the class level normalizer "%s".',
            $document::class,
            SkillNormalizer::class,
        ));

        $this->resolver->resolve($document::class, new ReflectionProperty($document, 'id'));
    }

    public function testResolveFailsForADocumentWithANormalizerFromAnInterface(): void
    {
        $document = new class ('1') extends DocumentBase implements NormalizedContract {
        };

        $this->expectException(DocumentHasClassNormalizer::class);

        $this->resolver->resolve($document::class, new ReflectionProperty($document, 'id'));
    }
}
