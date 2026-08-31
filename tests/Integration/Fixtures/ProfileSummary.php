<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

final readonly class ProfileSummary
{
    public function __construct(
        public string $name,
        public Status $status,
    ) {
    }
}
