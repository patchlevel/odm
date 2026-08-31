<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DiscriminatorMap
{
    /** @param array<string, class-string> $map */
    public function __construct(
        public array $map,
        public string $field = '_type',
    ) {
    }
}
