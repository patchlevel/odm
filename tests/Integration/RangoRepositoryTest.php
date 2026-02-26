<?php

declare(strict_types=1);

namespace Patchlevel\ODM\Tests\Integration;

use Patchlevel\Hydrator\CoreExtension;
use Patchlevel\Hydrator\HydratorBuilder;
use Patchlevel\ODM\Hydrator\ODMExtension;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\ODM\Tests\Integration\Fixtures\RangoDocument;
use Patchlevel\Rango\Client;
use Patchlevel\Rango\Database;
use PHPUnit\Framework\TestCase;

use function array_map;
use function getenv;
use function iterator_to_array;

class RangoRepositoryTest extends TestCase
{
    protected RangoRepositoryManager $repositoryManager;
    private Database $database;

    public function setUp(): void
    {
        $client = new Client(getenv('POSTGRES_URI'));

        $documentMetadataFactory = new AttributeDocumentMetadataFactory();

        $hydrator = (new HydratorBuilder())
            ->useExtension(new CoreExtension())
            ->useExtension(new ODMExtension($documentMetadataFactory))
            ->build();

        $this->database = $client->selectDatabase('patchlevel');

        $this->repositoryManager = new RangoRepositoryManager(
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
        $repository = $this->repositoryManager->get(RangoDocument::class);

        $document = new RangoDocument('r-1', 'Rango', 'active');
        $repository->save($document);

        $raw = $repository->collection()->findOne(['_id' => 'r-1']);

        self::assertNotNull($raw);
        self::assertSame(
            ['_id' => 'r-1', 'name' => 'Rango', 'status' => 'active'],
            $raw,
        );
    }

    public function testLoad(): void
    {
        $repository = $this->repositoryManager->get(RangoDocument::class);

        $repository->collection()->insertOne(['_id' => 'r-1', 'name' => 'Rango', 'status' => 'active']);
        $loaded = $repository->load('r-1');

        self::assertEquals(new RangoDocument('r-1', 'Rango', 'active'), $loaded);
    }

    public function testHas(): void
    {
        $repository = $this->repositoryManager->get(RangoDocument::class);

        self::assertFalse($repository->has('r-1'));

        $repository->collection()->insertOne(['_id' => 'r-1', 'name' => 'Rango', 'status' => 'active']);

        self::assertTrue($repository->has('r-1'));
    }

    public function testCount(): void
    {
        $repository = $this->repositoryManager->get(RangoDocument::class);

        self::assertSame(0, $repository->count());

        $repository->collection()->insertOne(['_id' => 'r-1', 'name' => 'Rango', 'status' => 'active']);
        $repository->collection()->insertOne(['_id' => 'r-2', 'name' => 'Beans', 'status' => 'inactive']);

        self::assertSame(2, $repository->count());
    }

    public function testFindWithFilter(): void
    {
        $repository = $this->repositoryManager->get(RangoDocument::class);

        $repository->collection()->insertOne(['_id' => 'r-1', 'name' => 'Rango', 'status' => 'active']);
        $repository->collection()->insertOne(['_id' => 'r-2', 'name' => 'Beans', 'status' => 'inactive']);
        $repository->collection()->insertOne(['_id' => 'r-3', 'name' => 'Rango', 'status' => 'active']);

        $results = iterator_to_array($repository->find(['status' => 'active']), false);

        self::assertCount(2, $results);
        self::assertSame(['r-1', 'r-3'], array_map(static fn (RangoDocument $doc) => $doc->id, $results));
    }

    public function testRemove(): void
    {
        $repository = $this->repositoryManager->get(RangoDocument::class);

        $repository->collection()->insertOne(['_id' => 'r-1', 'name' => 'Rango', 'status' => 'active']);
        $repository->collection()->insertOne(['_id' => 'r-2', 'name' => 'Beans', 'status' => 'inactive']);

        $repository->remove('r-1');

        self::assertFalse($repository->has('r-1'));
        self::assertNull($repository->collection()->findOne(['_id' => 'r-1']));
        self::assertSame(1, $repository->count());
    }

    public function testDropCollection(): void
    {
        $repository = $this->repositoryManager->get(RangoDocument::class);
        $repository->save(new RangoDocument('r-1', 'Rango', 'active'));

        $repository->dropCollection();

        self::assertSame(0, $repository->count());
    }

    public function testCreateCollectionCreatesIndexes(): void
    {
        $repository = $this->repositoryManager->get(RangoDocument::class);

        $repository->createCollection();

        $indexes = $repository->collection()->listIndexes();
        $indexNames = array_map(static fn (array $index): string => $index['name'], $indexes);

        self::assertContains('by_status', $indexNames);
    }

    public function testUpdateIndexesCreatesIndexes(): void
    {
        $repository = $this->repositoryManager->get(RangoDocument::class);

        $repository->updateIndexes();

        $indexes = $repository->collection()->listIndexes();
        $indexNames = array_map(static fn (array $index): string => $index['name'], $indexes);

        self::assertContains('by_status', $indexNames);
    }

    public function testUpdateIndexesDropsUnknownWhenRequested(): void
    {
        $repository = $this->repositoryManager->get(RangoDocument::class);

        $repository->updateIndexes();
        $repository->collection()->createIndex(['status' => 1], ['name' => 'custom_idx']);

        $repository->updateIndexes(true);

        $indexes = $repository->collection()->listIndexes();
        $indexNames = array_map(static fn (array $index): string => $index['name'], $indexes);

        self::assertContains('by_status', $indexNames);
        self::assertNotContains('custom_idx', $indexNames);
    }
}
