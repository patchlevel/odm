<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

use Patchlevel\Hydrator\Attribute\NormalizedName;

final readonly class Address
{
    public function __construct(
        #[NormalizedName('_street')]
        public string $street,
        #[NormalizedName('_city')]
        public string $city,
    ) {
    }
}
