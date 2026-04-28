<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration;

use MongoDB\Client;
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;

use function getenv;

final class MongoDBRepositoryTest extends RepositoryTestCase
{
    public function createRepositoryManager(): MongoDBRepositoryManager
    {
        $uri = getenv('MONGODB_URI');

        if (!$uri) {
            $this->markTestSkipped('MONGODB_URI is not set');
        }

        $client = new Client($uri);

        return MongoDBRepositoryManager::create($client);
    }
}
