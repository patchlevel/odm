<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

use Attribute;
use Patchlevel\Hydrator\Normalizer\InvalidType;
use Patchlevel\Hydrator\Normalizer\Normalizer;

use function is_string;

#[Attribute(Attribute::TARGET_CLASS)]
final class SkillNormalizer implements Normalizer
{
    /** @param array<string, mixed> $context */
    public function normalize(mixed $value, array $context): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Skill) {
            throw new InvalidType();
        }

        return $value->value;
    }

    /** @param array<string, mixed> $context */
    public function denormalize(mixed $value, array $context): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidType();
        }

        return new Skill($value);
    }
}
