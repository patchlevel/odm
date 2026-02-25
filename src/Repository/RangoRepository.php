<?php

namespace Patchlevel\ODM\Repository;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\ODM\Metadata\DocumentMetadata;
use Patchlevel\Rango\Collection;
use Patchlevel\Rango\Database;

/**
 * @template T of object
 * @implements Repository<T>
 */
final readonly class RangoRepository implements Repository
{
    public function __construct(
        private Database $database,
        private DocumentMetadata $metadata,
        private Hydrator $hydrator,
    ) {
    }

    /**
     * @param T $object
     */
    public function save(object $object): void
    {
        $data = $this->hydrator->extract($object);

        $this->collection()->insertOne($data);
    }

    /**
     * @return T
     */
    public function load(string $id): object
    {
        $data = $this->collection()->findOne(['_id' => $id]);

        return $this->hydrator->hydrate($this->metadata->className, $data);
    }

    public function remove(string $id): void
    {
        $this->collection()->deleteOne(['_id' => $id]);
    }

    /**
     * @param array<string, mixed> $filter
     * @return iterable<T>
     */
    public function find(array $filter = []): iterable
    {
        $cursor = $this->collection()->find($filter);

        foreach ($cursor as $document) {
            yield $this->hydrator->hydrate($this->metadata->className, $document);
        }
    }

    public function count(): int
    {
        return $this->collection()->countDocuments();
    }

    public function has(string $id): bool
    {
        return $this->collection()->countDocuments(['_id' => $id]) > 0;
    }

    public function database(): Database
    {
        return $this->database;
    }

    public function collection(): Collection
    {
        return $this->database->getCollection($this->metadata->collection);
    }

    public function createCollection(): void
    {
        $indexes = $this->metadata->indexes;

        $this->client->createCollection($this->metadata->collection);
        foreach ($indexes as $index) {
            $this->collection()->createIndex($index);
        }
    }

    public function dropCollection(): void
    {
        $this->database->getCollection($this->metadata->collection)->drop();
    }
}