<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

final readonly class Image extends Media
{
    public function __construct(string $id, string $title, public int $width)
    {
        parent::__construct($id, $title);
    }
}
