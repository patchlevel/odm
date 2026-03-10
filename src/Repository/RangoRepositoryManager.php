<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

use Patchlevel\Hydrator\CoreExtension;
use Patchlevel\Hydrator\Extension;
use Patchlevel\Hydrator\Hydrator;
use Patchlevel\Hydrator\StackHydratorBuilder;
use Patchlevel\ODM\Hydrator\ODMExtension;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
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

    /** @param list<Extension> $extensions */
    public function create(Database $database, array $extensions = []): self
    {
        $metadataFactory = new AttributeDocumentMetadataFactory();

        $builder = (new StackHydratorBuilder())
            ->useExtension(new CoreExtension())
            ->useExtension(new ODMExtension($metadataFactory));

        foreach ($extensions as $extension) {
            $builder->useExtension($extension);
        }

        return new self($database, $metadataFactory, $builder->build());
    }
}
