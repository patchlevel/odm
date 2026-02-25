<?php

namespace Patchlevel\ODM\Repository;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\ODM\Metadata\DocumentMetadataFactory;
use Patchlevel\Rango\Database;

final readonly class RangoRepositoryManager
{
    public function __construct(
        private Database $database,
        private DocumentMetadataFactory $metadataFactory,
        private Hydrator $hydrator
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $documentClass
     * @return Repository<T>
     */
    public function get(string $documentClass): Repository
    {
        return new RangoRepository(
            $this->database,
            $this->metadataFactory->metadata($documentClass),
            $this->hydrator
        );
    }
}