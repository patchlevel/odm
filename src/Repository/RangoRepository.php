<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\ODM\Metadata\DocumentMetadata;
use Patchlevel\Rango\Collection;
use Patchlevel\Rango\Database;

use function array_map;
use function in_array;
use function str_ends_with;

/**
 * @template T of object
 * @implements Repository<T>
 */
final readonly class RangoRepository implements Repository
{
    /** @param DocumentMetadata<T> $metadata */
    public function __construct(
        private Database $database,
        private DocumentMetadata $metadata,
        private Hydrator $hydrator,
    ) {
    }

    /** @param T $object */
    public function persist(object $object): void
    {
        if ($object::class !== $this->metadata->className) {
            throw new WrongClass($this->metadata->className, $object::class);
        }

        $data = $this->hydrator->extract($object);

        $this->collection()->insertOne($data);
    }

    /** @return T|null */
    public function find(string $id): object|null
    {
        $data = $this->collection()->findOne(['_id' => $id]);

        if ($data === null) {
            return null;
        }

        return $this->hydrator->hydrate($this->metadata->className, $data);
    }

    public function remove(string $id): void
    {
        $this->collection()->deleteOne(['_id' => $id]);
    }

    public function findAll(): iterable
    {
        $cursor = $this->collection()->find();

        foreach ($cursor as $document) {
            yield $this->hydrator->hydrate($this->metadata->className, $document);
        }
    }

    public function findBy(array $filter, array|null $orderBy = null, int|null $limit = null, int|null $offset = null): iterable
    {
        $options = [];

        if ($limit !== null) {
            $options['limit'] = $limit;
        }

        if ($offset !== null) {
            $options['skip'] = $offset;
        }

        if ($orderBy !== null) {
            $options['sort'] = array_map(
                static fn ($direction) => $direction === 'desc' ? -1 : 1,
                $orderBy,
            );
        }

        $cursor = $this->collection()->find($filter, $options);

        foreach ($cursor as $document) {
            yield $this->hydrator->hydrate($this->metadata->className, $document);
        }
    }

    public function findOneBy(array $filter = [], array|null $orderBy = null): object|null
    {
        $options = ['limit' => 1];

        if ($orderBy !== null) {
            $options['sort'] = array_map(
                static fn ($direction) => $direction === 'desc' ? -1 : 1,
                $orderBy,
            );
        }

        $data = $this->collection()->findOne($filter, $options);

        return $data ? $this->hydrator->hydrate($this->metadata->className, $data) : null;
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

    /** @return Collection<array<string, mixed>> */
    public function collection(): Collection
    {
        return $this->database->getCollection($this->metadata->collection);
    }

    public function createCollection(): void
    {
        $this->updateIndexes();
    }

    public function dropCollection(): void
    {
        $this->collection()->drop();
    }

    public function updateIndexes(bool $dropUnknown = false): void
    {
        $collection = $this->collection();

        $desiredNames = [];

        foreach ($this->metadata->indexes as $index) {
            $keys = array_map(
                static fn ($direction) => $direction === 'desc' ? -1 : 1,
                $index->keys,
            );

            $collection->createIndex($keys, [
                'name' => $index->name,
                'unique' => $index->unique,
            ]);

            $desiredNames[] = $index->name;
        }

        if (!$dropUnknown) {
            return;
        }

        foreach ($collection->listIndexes() as $index) {
            if (in_array($index['name'], $desiredNames, true)) {
                continue;
            }

            // Keep the built-in _id index.
            if (str_ends_with($index['name'], '_id_idx')) {
                continue;
            }

            $collection->dropIndex($index['name']);
        }
    }
}
