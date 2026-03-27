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
use Patchlevel\Rango\Client;

final class RangoRepositoryManager implements RepositoryManager
{
    /** @var array<string, RangoRepository<object>> */
    private array $repositories = [];

    public function __construct(
        private readonly Client $client,
        private readonly DocumentMetadataFactory $metadataFactory,
        private readonly Hydrator $hydrator,
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

    /** @param list<Extension> $extensions */
    public static function create(Client $client, array $extensions = []): self
    {
        $metadataFactory = new AttributeDocumentMetadataFactory();

        $builder = (new StackHydratorBuilder())
            ->useExtension(new CoreExtension())
            ->useExtension(new ODMExtension($metadataFactory));

        foreach ($extensions as $extension) {
            $builder->useExtension($extension);
        }

        return new self($client, $metadataFactory, $builder->build());
    }
}
