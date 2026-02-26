<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Repository;

interface RepositoryManager
{
    /**
     * @param class-string<T> $documentClass
     *
     * @return Repository<T>
     *
     * @template T of object
     */
    public function get(string $documentClass): Repository;
}
