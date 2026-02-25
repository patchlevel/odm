<?php

declare(strict_types=1);

namespace Patchlevel\Rango\Tests;

use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\ODM\Repository\Repository;
use Patchlevel\ODM\Repository\RepositoryManager;
use Patchlevel\Rango\Client;
use Patchlevel\Rango\Collection;
use Patchlevel\Rango\Database;

use function getenv;

final class PostgresIntegrationTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        getenv('POSTGRES_URI') ?: $this->markTestSkipped('POSTGRES_URI is not set');
    }

    protected function getRepository(): Repository
    {

    }

    protected function getRepositoryManager(): RepositoryManager
    {
        return new RangoRepositoryManager($this->getClient(), $this->getMetadataFactory(), $this->getHydrator());
    }

    protected function getClient(): Client
    {
        return new Client(getenv('POSTGRES_URI'));
    }

    protected function getCollection(): Collection
    {
        return $this->getDatabase()->getCollection('items');
    }

    protected function getDatabase(): Database
    {
        return $this->getClient()->getDatabase('test');
    }
}
