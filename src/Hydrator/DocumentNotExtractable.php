<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Hydrator;

use RuntimeException;

use function get_debug_type;
use function sprintf;

final class DocumentNotExtractable extends RuntimeException
{
    /** @param class-string $class */
    public function __construct(string $class, mixed $data)
    {
        parent::__construct(sprintf(
            'The hydrator returned "%s" instead of an array for the document "%s".',
            get_debug_type($data),
            $class,
        ));
    }
}
