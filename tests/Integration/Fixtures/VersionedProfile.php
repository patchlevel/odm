<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Version;

#[Document('versioned_profiles')]
final class VersionedProfile
{
    public function __construct(
        #[Id]
        public string $id,
        public string $name,
        #[Version]
        public int $version = 0,
    ) {
    }
}
