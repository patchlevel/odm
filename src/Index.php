<?php

declare(strict_types=1);

namespace Patchlevel\ODM;

final readonly class Index
{
    /** @param array<string, 'asc'|'desc'> $keys */
    public function __construct(
        public string $name,
        public array $keys,
        public bool $unique = false,
    ) {
    }
}
