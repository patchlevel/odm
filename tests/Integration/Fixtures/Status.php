<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration\Fixtures;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
