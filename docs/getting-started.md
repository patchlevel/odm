# Getting Started

This guide walks you through a complete example: you define a `Profile` document, set up a
repository manager, and then insert, load, query, update and remove documents. Only the initial
setup differs between MongoDB and PostgreSQL; every step after it is identical on both.

## Installation

Install the library together with the driver for your backend.

For PostgreSQL via [Rango](databases.md):

```bash
composer require patchlevel/odm patchlevel/rango
```
For MongoDB:

```bash
composer require patchlevel/odm mongodb/mongodb
```

## Define a document

A document is a plain PHP class marked with the `#[Document]` attribute. The collection name is the
first argument. One property carries the `#[Id]` attribute and becomes the document identifier.

```php
use Patchlevel\ODM\Attribute\Document;
use Patchlevel\ODM\Attribute\Id;
use Patchlevel\ODM\Attribute\Index;

#[Document('profiles')]
#[Index('by_status', ['status' => 'asc'])]
final class Profile
{
    /** @param list<Skill> $skills */
    public function __construct(
        #[Id]
        public readonly string $id,
        public string $name,
        public Status $status,
        public array $skills,
    ) {
    }
}
```
The document references two small value types and an enum:

```php
enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

#[SkillNormalizer]
final readonly class Skill
{
    public function __construct(
        public string $value,
    ) {
    }
}
```
:::note
The enum and the `Skill` value object are turned into scalars by the hydrator. The `#[SkillNormalizer]`
attribute is a custom normalizer. Both are explained on the [field mapping](field-mapping.md) page.
:::

## Set up the repository manager

The repository manager creates and caches one repository per document class. Build it with the
static `create()` factory and pass your database client. Pick the manager for your backend; the
repository you get back behaves the same either way.

For PostgreSQL via Rango:

```php
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\Rango\Client;

$client = new Client($_ENV['POSTGRES_URI']);

$manager = RangoRepositoryManager::create($client);
```
For MongoDB:

```php
use MongoDB\Client;
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;

$client = new Client($_ENV['MONGODB_URI']);

$manager = MongoDBRepositoryManager::create($client);
```
From here on the code is the same for both backends:

```php
$repository = $manager->get(Profile::class);
```

## Create the collection

Before storing documents, create the collection and its [indexes](documents.md#indexes):

```php
$repository->createCollection();
```
## Insert documents

`insert()` accepts one or many documents and writes them in a single operation.

```php
$repository->insert(
    new Profile('r-1', 'Rango', Status::ACTIVE, [new Skill('php')]),
    new Profile('r-2', 'Beans', Status::ACTIVE, [new Skill('node'), new Skill('js')]),
    new Profile('r-3', 'Elsa', Status::INACTIVE, [new Skill('mongodb')]),
);
```
## Load documents

Use `find()` to load a document by id, or `get()` if a missing document should raise an exception.

```php
$profile = $repository->find('r-1');      // Profile|null
$profile = $repository->get('r-1');       // Profile, throws DocumentNotFound when missing
```
## Query documents

`findBy()` filters documents and returns an iterable. You can sort, limit and offset the result.

```php
$profiles = iterator_to_array(
    $repository->findBy(
        filter: ['status' => Status::ACTIVE->value],
        orderBy: ['name' => 'asc'],
        limit: 10,
    ),
    false,
);
```
:::note
Filters and sorting accept the document property names, even when the stored field is renamed.
The [querying](repository.md#querying) section covers operators like `$in` and `$or`.
:::

## Update and remove

There is no Unit of Work, so changes are persisted only when you call `update()`. Remove documents
by id with `remove()`.

```php
$profile = $repository->get('r-2');
$profile->name = 'New Beans';
$repository->update($profile);

$repository->remove('r-3');
```
## Result

You now have a working document store: a `Profile` document is mapped through attributes, persisted
through a repository, and queried with filters and sorting, all without a Unit of Work.

## Learn more

* [How to define documents](documents.md)
* [How to use the repository](repository.md)
* [How to query documents](repository.md#querying)
* [How to encrypt sensitive data](encryption.md)
