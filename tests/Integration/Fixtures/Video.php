<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

final readonly class Video extends Media
{
    public function __construct(string $id, string $title, public int $duration)
    {
        parent::__construct($id, $title);
    }
}
