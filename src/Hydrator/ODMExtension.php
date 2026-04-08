<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Hydrator;

use Patchlevel\Hydrator\Extension;
use Patchlevel\Hydrator\StackHydratorBuilder;

final class ODMExtension implements Extension
{
    public function configure(StackHydratorBuilder $builder): void
    {
        $builder->addMiddleware(new ODMMiddleware());
    }
}
