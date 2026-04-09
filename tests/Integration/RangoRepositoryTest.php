<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration;

use Patchlevel\Hydrator\HydratorWithContext;
use Patchlevel\ODM\Metadata\DocumentMetadataFactory;
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\Rango\Client;

use function dump;
use function getenv;

final class RangoRepositoryTest extends RepositoryTestCase
{
    public function createRepositoryManager(
        HydratorWithContext $hydrator,
        DocumentMetadataFactory $documentMetadataFactory,
    ): MongoDBRepositoryManager|RangoRepositoryManager {
        $uri = getenv('POSTGRES_URI');

        if (!$uri) {
            $this->markTestSkipped('POSTGRES_URI is not set');
            dump('Skipping test due to missing POSTGRES_URI');
        }

        $client = new Client($uri);

        return new RangoRepositoryManager(
            $client,
            $documentMetadataFactory,
            $hydrator,
            'patchlevel',
        );
    }
}
