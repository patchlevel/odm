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
    /**
     * @param list<Skill>                                  $skills
     * @param list<Address>                                $addresses
     * @param array{height: int, addresses: list<Address>} $stats
     */
    public function __construct(
        #[Id]
        public string $id,
        #[NormalizedName('_personal_data')]
        public PersonalData $personalData,
        public Status $status,
        #[NormalizedName('_skills')]
        public array $skills,
        #[NormalizedName('_addresses')]
        public array $addresses,
        #[NormalizedName('_stats')]
        public array $stats,
    ) {
    }
}
