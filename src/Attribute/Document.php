<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Document
{
    public function __construct(
        public string $collection,
        public string|null $database = null,
    ) {
    }
}
