<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

use Patchlevel\ODM\Attribute\DiscriminatorMap;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index;

#[Document('media')]
#[Index('by_type', ['id' => 'asc'])]
#[DiscriminatorMap([
    'image' => Image::class,
    'video' => Video::class,
])]
abstract readonly class Media
{
    public function __construct(
        #[Id]
        public string $id,
        public string $title,
    ) {
    }
}
