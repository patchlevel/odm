<?php

namespace Patchlevel\ODM\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Index
{
    /**
     * @param string $name
     * @param array<string, 'asc'|'desc'> $keys
     * @param bool $unique
     */
    public function __construct(
        public string $name,
        public array $keys,
        public bool $unique = false
    ) {
    }
}