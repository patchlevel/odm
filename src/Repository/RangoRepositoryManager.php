<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

use Patchlevel\Hydrator\HydratorWithContext;
use Patchlevel\Hydrator\StackHydrator;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Metadata\DocumentMetadataFactory;
use Patchlevel\ODM\Metadata\StackHydratorFieldMappingResolver;
use Patchlevel\Rango\Client;

final class RangoRepositoryManager implements RepositoryManager
{
    /** @var array<string, RangoRepository<object>> */
    private array $repositories = [];

    public function __construct(
        private readonly Client $client,
        private readonly DocumentMetadataFactory $metadataFactory,
        private readonly HydratorWithContext $hydrator,
        private readonly string $defaultDatabase = 'public',
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

        $metadata = $this->metadataFactory->metadata($documentClass);

        $this->repositories[$documentClass] = new RangoRepository(
            $this->client->selectDatabase($metadata->database ?: $this->defaultDatabase),
            $this->metadataFactory->metadata($documentClass),
            $this->hydrator,
        );

        return $this->repositories[$documentClass];
    }

    public static function create(
        Client $client,
        StackHydrator $hydrator = new StackHydrator(),
    ): self {
        $metadataFactory = new AttributeDocumentMetadataFactory(
            new StackHydratorFieldMappingResolver($hydrator),
        );

        return new self($client, $metadataFactory, $hydrator);
    }
}
