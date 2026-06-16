# Databases

Patchlevel ODM runs on two backends: MongoDB and PostgreSQL through
[Rango](https://github.com/patchlevel/rango/). You pick the backend by choosing a repository manager.
Both managers implement the same interface and hand you repositories with an identical API, so your
documents and your application code stay the same across backends.

:::note
Both backends expose the same query API, so the [filter operators](repository.md#querying) and
[index definitions](documents.md#indexes) you write work the same on either one.
:::

## PostgreSQL via Rango

Require the Rango package and build a `RangoRepositoryManager` from a Rango client. The default
database is `public`.

```php
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\Rango\Client;

$client = new Client($_ENV['POSTGRES_URI']);

$manager = RangoRepositoryManager::create($client);
$repository = $manager->get(Profile::class);
```

## MongoDB

Require `mongodb/mongodb` and build a `MongoDBRepositoryManager` from a MongoDB client. The default
database is `default`.

```php
use MongoDB\Client;
use Patchlevel\ODM\Repository\MongoDBRepositoryManager;

$client = new Client($_ENV['MONGODB_URI']);

$manager = MongoDBRepositoryManager::create($client);
$repository = $manager->get(Profile::class);
```

## Choosing the database

A repository uses the manager's default database unless the document pins its own with the second
argument of `#[Document]`. The default database is set when constructing the manager directly.

```php
#[Document('profiles', database: 'analytics')]
final class Profile
{
    // ...
}
```
The `create()` factory uses the backend default (`public` or `default`). To set a different default
database, construct the manager yourself and pass the `defaultDatabase` argument.

## Manual construction

`create()` wires up sensible defaults, including the hydrator and the metadata factory. When you need
full control, for example to inject a custom hydrator for [encryption](encryption.md) or a shared
metadata factory, construct the manager directly.

```php
use Patchlevel\Hydrator\StackHydrator;
use Patchlevel\ODM\Metadata\AttributeDocumentMetadataFactory;
use Patchlevel\ODM\Metadata\StackHydratorFieldMappingResolver;
use Patchlevel\ODM\Repository\RangoRepositoryManager;
use Patchlevel\Rango\Client;

$client = new Client($_ENV['POSTGRES_URI']);
$hydrator = new StackHydrator();

$metadataFactory = new AttributeDocumentMetadataFactory(
    new StackHydratorFieldMappingResolver($hydrator),
);

$manager = new RangoRepositoryManager(
    $client,
    $metadataFactory,
    $hydrator,
    defaultDatabase: 'public',
);
```
:::tip
For most applications the static `create()` factory is enough. Reach for manual construction only when
you need to customize the hydrator or share infrastructure across managers.
:::

## Learn more

* [How to store and load documents](repository.md)
* [How to encrypt sensitive data](encryption.md)
* [How to define documents](documents.md)
