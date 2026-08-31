<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

final readonly class SkillPopularity
{
    public function __construct(
        public string $skill,
        public int $count,
    ) {
    }
}
