<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

use Patchlevel\ODM\Attribute\Id;

abstract class DocumentBase
{
    public function __construct(
        #[Id]
        public string $id,
    ) {
    }
}
