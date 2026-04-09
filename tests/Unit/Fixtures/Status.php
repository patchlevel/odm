<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Unit\Fixtures;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
