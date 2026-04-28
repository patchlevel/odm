<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration;

use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\Rango\Client;

use function getenv;

final class RangoRepositoryTest extends RepositoryTestCase
{
    public function createRepositoryManager(): RangoRepositoryManager
    {
        $uri = getenv('POSTGRES_URI');

        if (!$uri) {
            $this->markTestSkipped('POSTGRES_URI is not set');
        }

        $client = new Client($uri);

        return RangoRepositoryManager::create($client);
    }
}
