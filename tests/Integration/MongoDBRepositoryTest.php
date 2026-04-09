<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration;

use MongoDB\Client;
use Patchlevel\Hydrator\HydratorWithContext;
use Patchlevel\ODM\Metadata\DocumentMetadataFactory;
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;
use Patchlevel\ODM\Repository\RangoRepositoryManager;

use function getenv;

class MongoDBRepositoryTest extends RepositoryTest
{
    function createRepositoryManager(
        HydratorWithContext $hydrator,
        DocumentMetadataFactory $documentMetadataFactory,
    ): MongoDBRepositoryManager|RangoRepositoryManager {
        $uri = getenv('MONGODB_URI');

        if (!$uri) {
            self::markTestSkipped('MONGODB_URI is not set');
        }

        $client = new Client($uri);

        return new MongoDBRepositoryManager(
            $client,
            $documentMetadataFactory,
            $hydrator,
            'patchlevel',
        );
    }
}
