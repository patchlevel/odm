<?php

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

#[SkillNormalizer]
final readonly class Skill
{
    public function __construct(
        public string $value,
    ) {
    }
}