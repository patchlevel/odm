<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

#[SkillNormalizer]
final readonly class Skill
{
    public function __construct(
        public string $value,
    ) {
    }
}
