<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index;

#[Document('unique_profiles')]
#[Index('by_email', ['email' => 'asc'], unique: true)]
final readonly class UniqueProfile
{
    public function __construct(
        #[Id]
        public string $id,
        public string $email,
    ) {
    }
}
