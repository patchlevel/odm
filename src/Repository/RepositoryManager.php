<?php

namespace Patchlevel\ODM\Repository;

interface RepositoryManager
{
    public function get(string $documentClass): Repository;
}