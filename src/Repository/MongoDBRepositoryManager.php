<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

use MongoDB\Client;
use Patchlevel\Hydrator\HydratorWithContext;
use Patchlevel\Hydrator\StackHydrator;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Metadata\DocumentMetadataFactory;
use Patchlevel\ODM\Metadata\StackHydratorFieldMappingResolver;

final class MongoDBRepositoryManager implements RepositoryManager
{
    /** @var array<string, MongoDBRepository<object>> */
    private array $repositories = [];

    public function __construct(
        private readonly Client $client,
        private readonly DocumentMetadataFactory $metadataFactory,
        private readonly HydratorWithContext $hydrator,
        private readonly string $defaultDatabase = 'default',
    ) {
    }

    /**
     * @param class-string<T> $documentClass
     *
     * @return MongoDBRepository<T>
     *
     * @template T of object
     */
    public function get(string $documentClass): MongoDBRepository
    {
        if (isset($this->repositories[$documentClass])) {
            return $this->repositories[$documentClass];
        }

        $metadata = $this->metadataFactory->metadata($documentClass);

        $this->repositories[$documentClass] = new MongoDBRepository(
            $this->client->getDatabase($metadata->database ?: $this->defaultDatabase),
            $metadata,
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
