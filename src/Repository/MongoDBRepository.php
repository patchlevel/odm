<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Exception\ServerException;
use Patchlevel\Hydrator\HydratorWithContext;
use Patchlevel\ODM\Hydrator\DocumentHydrator;
use Patchlevel\ODM\Metadata\DocumentMetadata;

use function array_map;
use function count;
use function in_array;
use function iterator_to_array;
use function str_ends_with;

/**
 * @template T of object
 * @implements Repository<T>
 */
final readonly class MongoDBRepository implements Repository
{
    private Collection $collection;

    private DocumentHydrator $hydrator;

    private HydratorWithContext $viewHydrator;

    /** @param DocumentMetadata<T> $metadata */
    public function __construct(
        private Database $database,
        private DocumentMetadata $metadata,
        HydratorWithContext $hydrator,
    ) {
        $this->hydrator = new DocumentHydrator($hydrator, $metadata);
        $this->viewHydrator = $hydrator;
        $this->collection = $this->database->selectCollection($this->metadata->collection);
    }

    /** @param list<T> ...$objects */
    public function insert(object ...$objects): void
    {
        try {
            if (count($objects) === 1) {
                $object = $objects[0];

                if ($object::class !== $this->metadata->className) {
                    throw new WrongClass($this->metadata->className, $object::class);
                }

                $data = $this->hydrator->extract($object);
                $this->collection->insertOne($data);

                return;
            }

            $this->collection->insertMany(array_map(function (object $object): array {
                if ($object::class !== $this->metadata->className) {
                    throw new WrongClass($this->metadata->className, $object::class);
                }

                return $this->hydrator->extract($object);
            }, $objects));
        } catch (ServerException $e) {
            throw new InsertionFailed($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @param list<T> ...$objects */
    public function update(object ...$objects): void
    {
        if (count($objects) === 0) {
            return;
        }

        if (count($objects) === 1) {
            $object = $objects[0];

            if ($object::class !== $this->metadata->className) {
                throw new WrongClass($this->metadata->className, $object::class);
            }

            $data = $this->hydrator->extract($object);

            $this->collection->updateOne(['_id' => $data['_id']], ['$set' => $data]);

            return;
        }

        $this->collection->bulkWrite(array_map(
            function (object $object): array {
                if ($object::class !== $this->metadata->className) {
                    throw new WrongClass($this->metadata->className, $object::class);
                }

                $data = $this->hydrator->extract($object);

                return [
                    'updateOne' => [
                        ['_id' => $data['_id']],
                        ['$set' => $data],
                    ],
                ];
            },
            $objects,
        ));
    }

    /**
     * @return T
     *
     * @throws DocumentNotFound
     */
    public function get(string $id): object
    {
        $object = $this->find($id);

        if (!$object) {
            throw new DocumentNotFound(
                $this->metadata->className,
                $id,
                $this->collection->getDatabaseName(),
                $this->collection->getCollectionName(),
            );
        }

        return $object;
    }

    /** @return T|null */
    public function find(string $id): object|null
    {
        $data = $this->collection->findOne(['_id' => $id], [
            'typeMap' => ['root' => 'array', 'document' => 'array'],
        ]);

        if ($data === null) {
            return null;
        }

        return $this->hydrator->hydrate($this->metadata->className, $data);
    }

    public function remove(string ...$id): void
    {
        if (count($id) === 1) {
            $this->collection->deleteOne(['_id' => $id[0]]);

            return;
        }

        $this->collection->deleteMany(['_id' => ['$in' => $id]]);
    }

    /** @return iterable<T> */
    public function findAll(): iterable
    {
        $cursor = $this->collection->find([], [
            'typeMap' => ['root' => 'array', 'document' => 'array'],
        ]);

        foreach ($cursor as $document) {
            yield $this->hydrator->hydrate($this->metadata->className, $document);
        }
    }

    /**
     * @param array<string, mixed>             $filter
     * @param array<string, 'asc'|'desc'>|null $orderBy
     *
     * @return iterable<T>
     */
    public function findBy(
        array $filter,
        array|null $orderBy = null,
        int|null $limit = null,
        int|null $offset = null,
    ): iterable {
        $options = [];

        if ($limit !== null) {
            $options['limit'] = $limit;
        }

        if ($offset !== null) {
            $options['skip'] = $offset;
        }

        if ($orderBy !== null) {
            $options['sort'] = $this->metadata->mapSortingToFieldPaths($orderBy);
        }

        $options['typeMap'] = ['root' => 'array', 'document' => 'array'];

        $cursor = $this->collection->find($this->metadata->mapFilterToFieldPaths($filter), $options);

        foreach ($cursor as $document) {
            yield $this->hydrator->hydrate(
                $this->metadata->className,
                $document,
            );
        }
    }

    /**
     * @param array<string, mixed>             $filter
     * @param array<string, 'asc'|'desc'>|null $orderBy
     *
     * @return T|null
     */
    public function findOneBy(array $filter = [], array|null $orderBy = null): object|null
    {
        $options = [
            'limit' => 1,
            'typeMap' => ['root' => 'array', 'document' => 'array'],
        ];

        if ($orderBy !== null) {
            $options['sort'] = $this->metadata->mapSortingToFieldPaths($orderBy);
        }

        $data = $this->collection->findOne($this->metadata->mapFilterToFieldPaths($filter), $options);

        if ($data === null) {
            return null;
        }

        return $this->hydrator->hydrate($this->metadata->className, $data);
    }

    /**
     * @param list<array<string, mixed>> $pipeline
     * @param class-string<V>            $into
     *
     * @return iterable<V>
     *
     * @template V of object
     */
    public function aggregate(array $pipeline, string $into): iterable
    {
        $cursor = $this->collection->aggregate($pipeline, [
            'typeMap' => ['root' => 'array', 'document' => 'array'],
        ]);

        foreach ($cursor as $document) {
            /** @var array<string, mixed> $document */
            yield $this->viewHydrator->hydrate($into, $document);
        }
    }

    public function count(): int
    {
        return $this->collection->countDocuments();
    }

    public function has(string $id): bool
    {
        return $this->collection->countDocuments(['_id' => $id]) > 0;
    }

    public function database(): Database
    {
        return $this->database;
    }

    /** @return Collection<array<string, mixed>> */
    public function collection(): Collection
    {
        return $this->collection;
    }

    /** @return DocumentMetadata<T> */
    public function metadata(): DocumentMetadata
    {
        return $this->metadata;
    }

    public function createCollection(): void
    {
        $this->database->createCollection($this->metadata->collection);
        $this->updateIndexes();
    }

    public function dropCollection(): void
    {
        $this->collection->drop();
    }

    public function updateIndexes(bool $dropUnknown = false): void
    {
        $existingIndexes = [];
        foreach (iterator_to_array($this->collection->listIndexes()) as $index) {
            $existingIndexes[$index['name']] = true;
        }

        $desiredNames = [];

        foreach ($this->metadata->indexes as $index) {
            if (isset($existingIndexes[$index->name])) {
                $desiredNames[] = $index->name;
                continue;
            }

            $keys = $this->metadata->mapSortingToFieldPaths($index->keys);

            $this->collection->createIndex($keys, [
                'name' => $index->name,
                'unique' => $index->unique,
            ]);

            $desiredNames[] = $index->name;
        }

        if (!$dropUnknown) {
            return;
        }

        foreach (iterator_to_array($this->collection->listIndexes()) as $index) {
            if (in_array($index->getName(), $desiredNames, true)) {
                continue;
            }

            // Keep the built-in _id index.
            if (str_ends_with($index->getName(), '_id_')) {
                continue;
            }

            $this->collection->dropIndex($index->getName());
        }
    }
}
