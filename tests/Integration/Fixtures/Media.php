<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

use Patchlevel\ODM\Attribute\DiscriminatorMap;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;

#[Document('media_items')]
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
