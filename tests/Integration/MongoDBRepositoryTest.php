<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration;

use MongoDB\Client;
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;
use Patchlevel\ODM\Tests\Integration\Fixtures\Profile;
use Patchlevel\ODM\Tests\Integration\Fixtures\SkillPopularity;

use function getenv;
use function iterator_to_array;

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

    public function testAggregateGroupIntoView(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertMany([
            ['_id' => 'r-1', 'name' => 'Rango', 'status' => 'active', 'skills' => ['php', 'go']],
            ['_id' => 'r-2', 'name' => 'Beans', 'status' => 'active', 'skills' => ['php']],
            ['_id' => 'r-3', 'name' => 'Elsa', 'status' => 'inactive', 'skills' => ['go']],
        ]);

        $views = iterator_to_array($repository->aggregate([
            ['$unwind' => '$skills'],
            ['$group' => ['_id' => '$skills', 'count' => ['$sum' => 1]]],
            ['$project' => ['_id' => 0, 'skill' => '$_id', 'count' => 1]],
            ['$sort' => ['skill' => 1]],
        ], SkillPopularity::class), false);

        self::assertEquals([
            new SkillPopularity('go', 2),
            new SkillPopularity('php', 2),
        ], $views);
    }
}
