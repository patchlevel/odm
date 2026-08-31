# Repository

A repository stores and loads documents of a single type. You obtain one from a
[repository manager](databases.md) by passing the document class. The manager creates the repository
on first use and caches it, so calling `get()` repeatedly returns the same instance.

The manager you pick depends on your backend, but the repository it returns exposes the same methods
on both MongoDB and PostgreSQL:

```php
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;
use Patchlevel\ODM\Repository\RangoRepositoryManager;

// PostgreSQL via Rango
$manager = RangoRepositoryManager::create($rangoClient);

// MongoDB
$manager = MongoDBRepositoryManager::create($mongoClient);

$repository = $manager->get(Profile::class);
```
:::note
See the [databases](databases.md) page for how to create the client and manager for each backend.
:::

## No Unit of Work

Patchlevel ODM has no Unit of Work. The repository never tracks your documents and never persists
changes on its own. A document is written only when you explicitly call `insert()` or `update()`.
This keeps memory usage flat in long-running workers and prevents changes from leaking into the
database from unrelated parts of the code.

## Inserting

`insert()` accepts one or many documents. A single document is written with one operation, multiple
documents are written in a batch.

```php
$repository->insert(new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]));

$repository->insert(
    new Profile('r-2', 'Beans', Status::ACTIVE, [new Skill('js')]),
    new Profile('r-3', 'Elsa', Status::INACTIVE, [new Skill('go')]),
);
```
:::warning
Inserting a document whose id already exists fails with an `InsertionFailed` exception. The same
exception is raised when a [unique index](documents.md#indexes) is violated.
:::

## Updating

`update()` replaces the stored fields of existing documents. Because there is no change tracking, you
pass the full document you want to persist.

```php
$profile = $repository->get('r-1');
$profile->name = 'Rango Updated';

$repository->update($profile);
```
## Loading by id

`find()` returns the document or `null`. `get()` returns the document or throws `DocumentNotFound`
when it does not exist, which is convenient when the document is required.

```php
$profile = $repository->find('r-1');   // Profile|null
$profile = $repository->get('r-1');    // Profile, throws DocumentNotFound when missing
```
## Existence and counting

```php
$repository->has('r-1');   // bool
$repository->count();      // int, number of documents in the collection
```
## Iterating all documents

`findAll()` streams every document in the collection as an iterable.

```php
foreach ($repository->findAll() as $profile) {
    echo $profile->name;
}
```
:::tip
To filter, sort or paginate instead of loading everything, use `findBy()` and `findOneBy()` from the
[querying](#querying) section below.
:::

## Querying

Besides loading documents by id, the repository can query a collection with filters, sorting and
pagination. Queries use the document property names, even when a property is stored under a different
field name, so you never deal with the raw storage layout.

### Filtering

`findBy()` takes a filter array and returns an iterable of documents. Each key is a property name and
each value is the value to match.

```php
$active = iterator_to_array(
    $repository->findBy(['status' => 'active']),
    false,
);
```
:::note
`findBy()` returns a generator, so wrap it in `iterator_to_array()` when you need an array. Pass
`false` as the second argument to reindex the result.
:::

### Operators

Filter values support the MongoDB-style query operators. Operator keys start with `$` and are passed
through untouched, while the property names around them are still mapped to their stored field names.

```php
$selected = iterator_to_array(
    $repository->findBy(['id' => ['$in' => ['r-1', 'r-3']]]),
    false,
);

$result = iterator_to_array(
    $repository->findBy([
        '$or' => [
            ['status' => 'active'],
            ['name' => 'Rango'],
        ],
    ]),
    false,
);
```
:::tip
The same operators work on both backends, because MongoDB and
[Rango](https://github.com/patchlevel/rango/) share the same query API.
:::

### Sorting

Pass an `orderBy` array of property names mapped to `asc` or `desc`.

```php
$sorted = iterator_to_array(
    $repository->findBy([], orderBy: ['name' => 'asc']),
    false,
);
```
### Pagination

Use `limit` and `offset` to page through a result set.

```php
$page = iterator_to_array(
    $repository->findBy(
        filter: ['status' => 'active'],
        orderBy: ['name' => 'asc'],
        limit: 10,
        offset: 20,
    ),
    false,
);
```
### Fetching a single document

`findOneBy()` returns the first matching document or `null`. It accepts the same filter and an
optional `orderBy`.

```php
$profile = $repository->findOneBy(['name' => 'Beans']);

$newest = $repository->findOneBy([], orderBy: ['name' => 'desc']);
```
### Filtering on nested properties

You can filter and sort on nested properties using dot notation. The path is resolved through the
[field mapping](field-mapping.md), so renamed fields are handled automatically.

```php
$result = iterator_to_array(
    $repository->findBy(['personalData.name' => 'Rango']),
    false,
);
```
:::warning
Every property in a filter or sort must exist on the document. An unknown path raises
`UnknownPropertyPath`, which lists the properties that are available at that level.
:::

## Aggregations

`aggregate()` runs an aggregation pipeline against the collection and hydrates every result document
into a class you choose. This gives you a read-only "view" model: a plain class shaped like the
pipeline output, with no `#[Document]` attribute and no id.

```php
final readonly class SkillPopularity
{
    public function __construct(
        public string $skill,
        public int $count,
    ) {
    }
}

$views = iterator_to_array(
    $repository->aggregate([
        ['$unwind' => '$skills'],
        ['$group' => ['_id' => '$skills', 'count' => ['$sum' => 1]]],
        ['$project' => ['_id' => 0, 'skill' => '$_id', 'count' => 1]],
        ['$sort' => ['skill' => 1]],
    ], SkillPopularity::class),
    false,
);
```

Like `findBy()`, this returns a generator, so wrap it in `iterator_to_array()` when you need an
array. Each document is hydrated with the same hydrator used for documents, so normalizers and value
objects on the result class work as usual.

:::warning
Unlike `findBy()`, the pipeline is passed to the backend untouched. Its stages use the **stored
field names**, not property names, and there is no mapping through the [field mapping](field-mapping.md).
Shape the output with a `$project` stage so its keys match the properties of your result class.
:::

:::note
MongoDB and [Rango](https://github.com/patchlevel/rango/) share a common subset of pipeline stages
(`$match`, `$sort`, `$limit`, `$skip`, `$project`, `$unwind`, `$group`, `$lookup`). Stages or
operators beyond that subset only work on MongoDB.
:::

## Removing

`remove()` deletes documents by id and accepts one or many ids.

```php
$repository->remove('r-1');
$repository->remove('r-2', 'r-3');
```
## Managing the collection

The repository can create and drop its own collection. `createCollection()` also creates the
[indexes](documents.md#indexes) declared on the document.

```php
$repository->createCollection();
$repository->dropCollection();
```
:::warning
`dropCollection()` permanently deletes the collection and all of its documents.
:::

## Learn more

* [How to define indexes and unique constraints](documents.md#indexes)
* [How field names and nested objects are mapped](field-mapping.md)
* [How to wire up MongoDB or PostgreSQL](databases.md)
