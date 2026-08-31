<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

final readonly class Video extends Media
{
    public function __construct(string $id, string $title, public int $duration, public string $format = 'mp4')
    {
        parent::__construct($id, $title);
    }
}
