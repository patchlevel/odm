<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\ODM\Metadata\DocumentMetadataFactory;
use Patchlevel\Rango\Database;

final class RangoRepositoryManager implements RepositoryManager
{
    /** @var array<string, Repository<object>> */
    private array $repositories = [];

    public function __construct(
        private readonly Database $database,
        private readonly DocumentMetadataFactory $metadataFactory,
        private readonly Hydrator $hydrator,
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
        if (isset($this->repositories[$documentClass])) {
            return $this->repositories[$documentClass];
        }

        $this->repositories[$documentClass] = new RangoRepository(
            $this->database,
            $this->metadataFactory->metadata($documentClass),
            $this->hydrator,
        );

        return $this->repositories[$documentClass];
    }
}
