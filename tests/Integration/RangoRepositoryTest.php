<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration;

use Patchlevel\Hydrator\CoreExtension;
use Patchlevel\Hydrator\StackHydratorBuilder;
use Patchlevel\ODM\Hydrator\ODMExtension;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\ODM\Tests\Integration\Fixtures\Profile;
use Patchlevel\ODM\Tests\Integration\Fixtures\Skill;
use Patchlevel\ODM\Tests\Integration\Fixtures\Status;
use Patchlevel\ODM\Tests\Integration\Fixtures\UniqueProfile;
use Patchlevel\Rango\Client;
use Patchlevel\Rango\Exception\QueryException;
use PHPUnit\Framework\TestCase;

use function array_map;
use function getenv;
use function iterator_to_array;

class RangoRepositoryTest extends TestCase
{
    protected RangoRepositoryManager $repositoryManager;
    private Client $client;

    public function setUp(): void
    {
        $uri = getenv('POSTGRES_URI');

        if (!$uri) {
            self::markTestSkipped('POSTGRES_URI is not set');
        }

        $this->client = new Client($uri);

        $documentMetadataFactory = new AttributeDocumentMetadataFactory();

        $hydrator = (new StackHydratorBuilder())
            ->useExtension(new CoreExtension())
            ->useExtension(new ODMExtension($documentMetadataFactory))
            ->build();

        $this->client->dropDatabase('patchlevel');

        $this->repositoryManager = new RangoRepositoryManager(
            $this->client,
            $documentMetadataFactory,
            $hydrator,
            'patchlevel',
        );
    }

    protected function tearDown(): void
    {
        $this->client->dropDatabase('patchlevel');
    }

    public function testInsert(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $document = new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]);
        $repository->insert($document);

        $raw = $repository->collection()->findOne(['_id' => 'r-1']);

        self::assertNotNull($raw);
        self::assertEquals(
            ['_id' => 'r-1', 'name' => 'Rango', 'status' => 'active', 'skills' => ['php']],
            $raw,
        );
    }

    public function testInsertMany(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->insert(
            new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]),
            new Profile('r-2', 'Beans', Status::INACTIVE, [new Skill('js')]),
        );

        self::assertSame(2, $repository->count());
        self::assertTrue($repository->has('r-1'));
        self::assertTrue($repository->has('r-2'));
    }

    public function testUpdate(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);

        $repository->update(new Profile('r-1', 'Updated', Status::INACTIVE, [new Skill('go')]));

        $updated = $repository->collection()->findOne(['_id' => 'r-1']);

        self::assertNotNull($updated);
        self::assertEquals(
            ['_id' => 'r-1', 'name' => 'Updated', 'status' => 'inactive', 'skills' => ['go']],
            $updated,
        );
    }

    public function testUpdateMany(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-2',
            'name' => 'Beans',
            'status' => 'inactive',
            'skills' => ['js'],
        ]);

        $repository->update(
            new Profile('r-1', 'Rango Updated', Status::ACTIVE, [new Skill('php'), new Skill('mongodb')]),
            new Profile('r-2', 'Beans Updated', Status::ACTIVE, [new Skill('ts')]),
        );

        $r1 = $repository->collection()->findOne(['_id' => 'r-1']);
        $r2 = $repository->collection()->findOne(['_id' => 'r-2']);

        self::assertNotNull($r1);
        self::assertNotNull($r2);
        self::assertSame('Rango Updated', $r1['name']);
        self::assertSame('Beans Updated', $r2['name']);
    }

    public function testLoad(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);
        $loaded = $repository->find('r-1');

        self::assertEquals(new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]), $loaded);
    }

    public function testHas(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        self::assertFalse($repository->has('r-1'));

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);

        self::assertTrue($repository->has('r-1'));
    }

    public function testCount(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        self::assertSame(0, $repository->count());

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-2',
            'name' => 'Beans',
            'status' => 'inactive',
            'skills' => ['js'],
        ]);

        self::assertSame(2, $repository->count());
    }

    public function testFindWithFilter(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-2',
            'name' => 'Beans',
            'status' => 'inactive',
            'skills' => ['js'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-3',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['tracking'],
        ]);

        $results = iterator_to_array($repository->findBy(['status' => 'active']), false);

        self::assertCount(2, $results);
        self::assertSame(['r-1', 'r-3'], array_map(static fn (Profile $doc) => $doc->id, $results));
    }

    public function testFindWithLimit(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-2',
            'name' => 'Beans',
            'status' => 'inactive',
            'skills' => ['js'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-3',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['tracking'],
        ]);

        $results = iterator_to_array($repository->findBy([], limit: 2), false);

        self::assertCount(2, $results);
        self::assertSame(['r-1', 'r-2'], array_map(static fn (Profile $doc) => $doc->id, $results));
    }

    public function testFindWithOffset(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-2',
            'name' => 'Beans',
            'status' => 'inactive',
            'skills' => ['js'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-3',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['tracking'],
        ]);

        $results = iterator_to_array($repository->findBy([], offset: 1), false);

        self::assertCount(2, $results);
        self::assertSame(['r-2', 'r-3'], array_map(static fn (Profile $doc) => $doc->id, $results));
    }

    public function testFindWithSort(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-2',
            'name' => 'Beans',
            'status' => 'inactive',
            'skills' => ['js'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-3',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['tracking'],
        ]);

        $results = iterator_to_array($repository->findBy([], orderBy: ['name' => 'asc']), false);

        self::assertCount(3, $results);
        self::assertSame(['r-2', 'r-1', 'r-3'], array_map(static fn (Profile $doc) => $doc->id, $results));
    }

    public function testFindOne(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);

        $repository->collection()->insertOne([
            '_id' => 'r-2',
            'name' => 'Beans',
            'status' => 'inactive',
            'skills' => ['js'],
        ]);

        $result = $repository->findOneBy(['name' => 'Beans']);

        self::assertNotNull($result);
        self::assertSame('r-2', $result->id);
    }

    public function testNotFindOne(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);

        $repository->collection()->insertOne([
            '_id' => 'r-2',
            'name' => 'Beans',
            'status' => 'inactive',
            'skills' => ['js'],
        ]);

        $result = $repository->findOneBy(['name' => 'Foo']);

        self::assertNull($result);
    }

    public function testRemove(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);

        $repository->collection()->insertOne([
            '_id' => 'r-2',
            'name' => 'Beans',
            'status' => 'inactive',
            'skills' => ['js'],
        ]);

        $repository->remove('r-1');

        self::assertFalse($repository->has('r-1'));
        self::assertNull($repository->collection()->findOne(['_id' => 'r-1']));
        self::assertSame(1, $repository->count());
    }

    public function testRemoveMany(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->collection()->insertOne([
            '_id' => 'r-1',
            'name' => 'Rango',
            'status' => 'active',
            'skills' => ['php'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-2',
            'name' => 'Beans',
            'status' => 'inactive',
            'skills' => ['js'],
        ]);
        $repository->collection()->insertOne([
            '_id' => 'r-3',
            'name' => 'Elsa',
            'status' => 'active',
            'skills' => ['go'],
        ]);

        $repository->remove('r-1', 'r-3');

        self::assertFalse($repository->has('r-1'));
        self::assertFalse($repository->has('r-3'));
        self::assertTrue($repository->has('r-2'));
        self::assertSame(1, $repository->count());
    }

    public function testDropCollection(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);
        $repository->insert(new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]));

        $repository->dropCollection();

        self::assertSame(0, $repository->count());
    }

    public function testCreateCollectionCreatesIndexes(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->createCollection();

        $indexes = iterator_to_array($repository->collection()->listIndexes(), false);
        $indexNames = array_map(static fn ($index): string => $index['name'], $indexes);

        self::assertContains('by_status', $indexNames);
    }

    public function testUpdateIndexesCreatesIndexes(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->updateIndexes();

        $indexes = iterator_to_array($repository->collection()->listIndexes(), false);
        $indexNames = array_map(static fn ($index): string => $index['name'], $indexes);

        self::assertContains('by_status', $indexNames);
    }

    public function testUpdateIndexesDropsUnknownWhenRequested(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $repository->updateIndexes();
        $repository->collection()->createIndex(['status' => 1], ['name' => 'custom_idx']);

        $repository->updateIndexes(true);

        $indexes = iterator_to_array($repository->collection()->listIndexes(), false);
        $indexNames = array_map(static fn ($index): string => $index['name'], $indexes);

        self::assertContains('by_status', $indexNames);
        self::assertNotContains('custom_idx', $indexNames);
    }

    public function testUniqueIndexIsEnforced(): void
    {
        $repository = $this->repositoryManager->get(UniqueProfile::class);

        $repository->updateIndexes();
        $repository->collection()->insertOne([
            '_id' => 'u-1',
            'email' => 'rango@example.com',
        ]);

        $this->expectException(QueryException::class);

        $repository->collection()->insertOne([
            '_id' => 'u-2',
            'email' => 'rango@example.com',
        ]);
    }
}
