<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

use Patchlevel\Hydrator\Attribute\NormalizedName;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index;

#[Document('rango_documents')]
#[Index('by_status', ['status' => 'asc'])]
final readonly class Profile
{
    /** @param list<Skill> $skills */
    public function __construct(
        #[Id]
        public string $id,
        #[NormalizedName('__personal_data__')]
        public PersonalData $personalData,
        public Status $status,
        #[NormalizedName('_skills')]
        public array $skills,
    ) {
    }
}
