<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

final class Circle extends Shape
{
    public function __construct(string $id, public int $radius)
    {
        parent::__construct($id);
    }
}
