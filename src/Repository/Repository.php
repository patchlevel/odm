<?php

namespace Patchlevel\ODM\Repository;

/**
 * @template T of object
 */
interface Repository
{
    /**
     * @param T $object
     */
    public function save(object $object): void;

    /**
     * @param string $id
     * @return T
     */
    public function load(string $id): object;

    public function remove(string $id): void;

    /**
     * @param array<string, mixed> $filter
     * @return iterable<T>
     */
    public function find(array $filter = []): iterable;

    public function count(): int;

    public function has(string $id): bool;

    public function createCollection(): void;

    public function dropCollection(): void;
}