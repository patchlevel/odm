<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

/** @template T of object */
interface Repository
{
    /** @param T $object */
    public function persist(object $object): void;

    /** @return T|null */
    public function find(string $id): object|null;

    public function remove(string $id): void;

    /** @return iterable<T> */
    public function findAll(): iterable;

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
    ): iterable;

    /**
     * @param array<string, mixed>             $filter
     * @param array<string, 'asc'|'desc'>|null $orderBy
     *
     * @return T|null
     */
    public function findOneBy(array $filter = [], array|null $orderBy = null): object|null;

    public function count(): int;

    public function has(string $id): bool;

    public function createCollection(): void;

    public function dropCollection(): void;

    public function updateIndexes(bool $dropUnknown = false): void;
}
