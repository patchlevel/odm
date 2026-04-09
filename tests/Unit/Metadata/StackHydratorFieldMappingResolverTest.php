<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Metadata;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\PropertyMetadata;
use Patchlevel\Hydrator\Normalizer\Normalizer;
use Patchlevel\Hydrator\StackHydrator;
use Patchlevel\ODM\Metadata\FieldMapping;
use Patchlevel\ODM\Metadata\StackHydratorFieldMappingResolver;
use Patchlevel\ODM\Tests\Unit\Fixtures\Profile;
use Patchlevel\ODM\Tests\Unit\Fixtures\PersonalData;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class StackHydratorFieldMappingResolverTest extends TestCase
{
    public function testResolvePropertyWithoutNormalizer(): void
    {
        $resolver = new StackHydratorFieldMappingResolver(new StackHydrator());

        $mapping = $resolver->resolve(new ReflectionProperty(PersonalData::class, 'name'));

        self::assertEquals(new FieldMapping('full_name'), $mapping);
    }

    public function testResolvePropertyWithObjectNormalizer(): void
    {
        $resolver = new StackHydratorFieldMappingResolver(new StackHydrator());


        $mapping = $resolver->resolve(new ReflectionProperty(Profile::class, 'personalData'));

        self::assertEquals(
            new FieldMapping('_profile', [
                'name' => new FieldMapping('_name'),
                'age' => new FieldMapping('_age'),
            ]),
            $mapping,
        );
    }

    public function testResolvePropertyWithArrayOfObjectNormalizer(): void
    {
        $resolver = new StackHydratorFieldMappingResolver(new StackHydrator());


        $mapping = $resolver->resolve(new ReflectionProperty(Profile::class, 'skills'));

        self::assertEquals(
            new FieldMapping('_skills', [
                'value' => new FieldMapping('_value'),
            ]),
            $mapping,
        );
    }

    public function testResolvePropertyWithArrayShapeNormalizer(): void
    {
        $resolver = new StackHydratorFieldMappingResolver(new StackHydrator());

        $mapping = $resolver->resolve(new ReflectionProperty(Profile::class, 'stats'));

        self::assertEquals(
            new FieldMapping('_stats', [
                'item' => new FieldMapping('item', [
                    'value' => new FieldMapping('_value'),
                ]),
                'raw' => new FieldMapping('raw'),
            ]),
            $mapping,
        );
    }
}