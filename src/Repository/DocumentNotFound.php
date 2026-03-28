<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

use RuntimeException;

use function sprintf;

final class DocumentNotFound extends RuntimeException
{
    /** @param class-string $documentClass */
    public function __construct(
        string $documentClass,
        string $id,
        string $database,
        string $collection,
    ) {
        parent::__construct(sprintf(
            'Document "%s" with id "%s" not found in database "%s" and collection "%s".',
            $documentClass,
            $id,
            $database,
            $collection,
        ));
    }
}
