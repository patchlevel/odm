<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index;

#[Document('rango_documents')]
#[Index('by_status', ['status' => 'asc'])]
final readonly class RangoDocument
{
    public function __construct(
        #[Id]
        public string $id,
        public string $name,
        public string $status,
    ) {
    }
}
