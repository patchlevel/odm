<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

use Patchlevel\Hydrator\Attribute\NormalizedName;

final readonly class PersonalData
{
    public function __construct(
        #[NormalizedName('_name')]
        public string $name,
        #[NormalizedName('_age')]
        public int $age,
    ) {
    }
}
