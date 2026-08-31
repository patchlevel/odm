<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

use RuntimeException;

use function sprintf;

final class OptimisticLockFailed extends RuntimeException
{
    /** @param class-string $documentClass */
    public static function forDocument(string $documentClass, string $id, int $expectedVersion): self
    {
        return new self(sprintf(
            'Document "%s" with id "%s" was changed or removed by another process. Expected version %d in '
            . 'the database. Reload the document and retry the update.',
            $documentClass,
            $id,
            $expectedVersion,
        ));
    }

    /** @param class-string $documentClass */
    public static function forBatch(string $documentClass, int $expected, int $matched): self
    {
        return new self(sprintf(
            'Expected to update %d "%s" documents, but only %d matched. At least one document was changed or '
            . 'removed by another process. Reload the documents and retry the update.',
            $expected,
            $documentClass,
            $matched,
        ));
    }
}
