<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

use Attribute;
use Patchlevel\Hydrator\Normalizer\InvalidType;
use Patchlevel\Hydrator\Normalizer\NormalizerWithContext;

#[Attribute(Attribute::TARGET_CLASS)]
final class SkillNormalizer implements NormalizerWithContext
{
    /** @param array<string, mixed> $context */
    public function normalize(mixed $value, array $context = []): mixed
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
    public function denormalize(mixed $value, array $context = []): mixed
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
