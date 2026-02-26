<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\ODM\Metadata\DocumentMetadataFactory;
use Patchlevel\Rango\Database;

final readonly class RangoRepositoryManager implements RepositoryManager
{
    public function __construct(
        private Database $database,
        private DocumentMetadataFactory $metadataFactory,
        private Hydrator $hydrator,
    ) {
    }

    /**
     * @param class-string<T> $documentClass
     *
     * @return RangoRepository<T>
     *
     * @template T of object
     */
    public function get(string $documentClass): RangoRepository
    {
        return new RangoRepository(
            $this->database,
            $this->metadataFactory->metadata($documentClass),
            $this->hydrator,
        );
    }
}
