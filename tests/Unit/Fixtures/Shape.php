<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

use Patchlevel\ODM\Attribute\DiscriminatorMap;
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;

#[Document('shapes')]
#[DiscriminatorMap([
    'circle' => Circle::class,
    'square' => Square::class,
])]
abstract class Shape
{
    public function __construct(
        #[Id]
        private string $id,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }
}
