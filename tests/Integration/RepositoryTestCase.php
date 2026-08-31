<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration;

use Patchlevel\ODM\Repository\InsertionFailed;
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\ODM\Tests\Integration\Fixtures\Profile;
use Patchlevel\ODM\Tests\Integration\Fixtures\ProfileSummary;
use Patchlevel\ODM\Tests\Integration\Fixtures\Skill;
use Patchlevel\ODM\Tests\Integration\Fixtures\Status;
use Patchlevel\ODM\Tests\Integration\Fixtures\UniqueProfile;
use PHPUnit\Framework\TestCase;

use function array_map;
use function is_array;
use function iterator_to_array;

abstract class RepositoryTestCase extends TestCase
{
    protected MongoDBRepositoryManager|RangoRepositoryManager $repositoryManager;

    abstract public function createRepositoryManager(): MongoDBRepositoryManager|RangoRepositoryManager;

    public function setUp(): void
    {
        $this->repositoryManager = $this->createRepositoryManager();
        $this->repositoryManager->get(Profile::class)->database()->drop();
    }

    public function testInsert(): void
    {
        $repository = $this->repositoryManager->get(Profile::class);

        $document = new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]);
        $repository->insert($document);

        $raw = $repository->collection()->findOne(['_id' => 'r-1']);

        self::assertNotNull($raw);
        self::assertSame('r-1', $raw['_id']);
        self::assertSame('Rango', $raw['name']);
        self::assertSame('active', $raw['status']);

        $skills = $raw['skills'];
        if (!is_array($skills)) {
            $skills = iterator_to_array($skills);
        }

        self::assertSame(['php'], $skills);
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
        self::assertSame('Updated', $updated['name']);
        self::assertSame('inactive', $updated['status']);
        self::assertSame(
            ['go'],
            is_array($updated['skills']) ? $updated['skills'] : iterator_to_array($updated['skills']),
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

    public function testFindWithFilterById(): void
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

        $results = iterator_to_array($repository->findBy(['id' => ['$in' => ['r-1', 'r-3']]]), false);

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

    public function testAggregateIntoView(): void
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

        $views = iterator_to_array($repository->aggregate([
            ['$match' => ['status' => 'active']],
            ['$project' => ['name' => 1, 'status' => 1]],
            ['$sort' => ['name' => 1]],
        ], ProfileSummary::class), false);

        self::assertCount(2, $views);
        self::assertContainsOnlyInstancesOf(ProfileSummary::class, $views);
        self::assertEquals(new ProfileSummary('Elsa', Status::ACTIVE), $views[0]);
        self::assertEquals(new ProfileSummary('Rango', Status::ACTIVE), $views[1]);
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

        $repository->insert(
            new UniqueProfile('r-1', 'Rango'),
        );

        $this->expectException(InsertionFailed::class);

        $repository->insert(
            new UniqueProfile('r-1', 'Rango'),
        );
    }
}
