<?php

namespace Patchlevel\ODM;

final readonly class Index
{
    public function __construct(
        public string $name,
        public array $keys,
        public bool $unique = false
    ) {
    }
}