<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

use RuntimeException;

use function sprintf;

class WrongClass extends RuntimeException
{
    public function __construct(string $expected, string $given)
    {
        parent::__construct(sprintf('Expected class "%s", got "%s".', $expected, $given));
    }
}
