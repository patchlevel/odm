<?php

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

use Attribute;
use Patchlevel\Hydrator\Normalizer\Normalizer;

#[Attribute(Attribute::TARGET_CLASS)]
final class SkillNormalizer implements Normalizer
{
    public function normalize(mixed $value, array $context): mixed
    {
        if ($value === null) {
            return null;
        }

        return $value->value;
    }

    public function denormalize(mixed $value, array $context): mixed
    {
        if ($value === null) {
            return null;
        }

        return new Skill($value);
    }
}