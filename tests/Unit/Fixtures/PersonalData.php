<?php

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

use Patchlevel\Hydrator\Attribute\NormalizedName;

final readonly class PersonalData
{
    public function __construct(
        #[NormalizedName('full_name')]
        public string $name,
        #[NormalizedName('age_in_years')]
        public int $age,
    )
    {
    }
}