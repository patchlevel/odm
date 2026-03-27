<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration;

use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Driver\Exception\BulkWriteException;
use Patchlevel\Hydrator\CoreExtension;
use Patchlevel\Hydrator\StackHydratorBuilder;
use Patchlevel\ODM\Hydrator\ODMExtension;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;
use Patchlevel\ODM\Tests\Integration\Fixtures\Profile;
use Patchlevel\ODM\Tests\Integration\Fixtures\Skill;
use Patchlevel\ODM\Tests\Integration\Fixtures\Status;
use Patchlevel\ODM\Tests\Integration\Fixtures\UniqueProfile;
use PHPUnit\Framework\TestCase;

use function array_map;
use function getenv;
use function iterator_to_array;
use function is_array;

class MongoDBRepositoryTest extends TestCase
{
    protected MongoDBRepositoryManager $repositoryManager;
    private Database $database;

    public function setUp(): void
    {
        $client = new Client(getenv('MONGODB_URI'));

        $documentMetadataFactory = new AttributeDocumentMetadataFactory();

        $hydrator = (new StackHydratorBuilder())
            ->useExtension(new CoreExtension())
            ->useExtension(new ODMExtension($documentMetadataFactory))
            ->build();

        $this->database = $client->selectDatabase('patchlevel');
        $this->database->drop();

        $this->repositoryManager = new MongoDBRepositoryManager(
            $this->database,
            $documentMetadataFactory,
            $hydrator,
        );
    }

    protected function tearDown(): void
    {
        $this->database->drop();
    }

    public function testSave(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $document = new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]);
        $repository->persist($document);

        $raw = $repository->collection()->findOne(['_id' => 'r-1']);

        self::assertNotNull($raw);
        self::assertSame('r-1', $raw['_id']);
        self::assertSame('Rango', $raw['name']);
        self::assertSame('active', $raw['status']);

        $skills = $raw['skills'];
        if (! is_array($skills)) {
            $skills = iterator_to_array($skills);
        }

        self::assertSame(['php'], $skills);
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

    public function testDropCollection(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);
        $repository->persist(new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]));

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
        $repository->collection()->createIndex(['name' => 1], ['name' => 'custom_idx']);

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

        $this->expectException(BulkWriteException::class);

        $repository->collection()->insertOne([
            '_id' => 'u-2',
            'email' => 'rango@example.com',
        ]);
    }
}
